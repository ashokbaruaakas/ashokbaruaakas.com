<?php

declare(strict_types=1);

namespace App\Actions\Assistant;

use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

final readonly class ReadWebPageAction
{
    private const int MAX_REDIRECTS = 5;

    private const int MAX_BYTES = 5_000_000;

    private const int MAX_TEXT = 15_000;

    private const string BLOCKS = 'p, div, section, article, main, aside, header, li, dt, dd, tr, h1, h2, h3, h4, h5, h6, br, hr, pre, blockquote';

    private const array READABLE_TYPES = ['text/html', 'application/xhtml+xml', 'text/plain'];

    /**
     * Fetch a public web page and return what a reader would see on it: its title and description, any job postings
     * it describes in structured data, and its text. Only public addresses are fetched, after every redirect.
     *
     * @return array{url: string, title: string|null, description: string|null, job_postings: list<array<mixed>>, text: string}
     *
     * @throws RuntimeException when the page cannot be read, with a message the owner can be shown
     */
    public function handle(string $url): array
    {
        [$response, $url] = $this->fetch($url);
        $type = Str::before($response->header('Content-Type'), ';');

        if ($type !== '' && ! in_array(mb_strtolower(mb_trim($type)), self::READABLE_TYPES, true)) {
            throw new RuntimeException("The page is a {$type} file, not a web page.");
        }

        if (mb_strtolower(mb_trim($type)) === 'text/plain') {
            return ['url' => $url, 'title' => null, 'description' => null, 'job_postings' => [], 'text' => $this->limit($this->tidy($response->body()))];
        }

        $document = HTMLDocument::createFromString($response->body(), LIBXML_NOERROR, 'UTF-8');

        return [
            'url' => $url,
            'title' => $this->clean($document->title) ?? $this->meta($document, 'og:title'),
            'description' => $this->meta($document, 'description') ?? $this->meta($document, 'og:description'),
            'job_postings' => $this->jobPostings($document),
            'text' => $this->limit($this->text($document)),
        ];
    }

    /**
     * Follow redirects one hop at a time so every address is checked, and pin each request to the checked address.
     *
     * @return array{Response, string}
     */
    private function fetch(string $url): array
    {
        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            [$host, $port, $address] = $this->publicTarget($url);

            try {
                $response = Http::withoutRedirecting()
                    ->timeout(15)
                    ->connectTimeout(5)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0 Safari/537.36',
                        'Accept' => 'text/html,application/xhtml+xml,text/plain;q=0.9,*/*;q=0.5',
                        'Accept-Language' => 'en;q=1.0, *;q=0.5',
                    ])
                    ->withOptions([
                        'curl' => [CURLOPT_RESOLVE => ["{$host}:{$port}:{$address}"]],
                        'on_headers' => function (ResponseInterface $response): void {
                            if ((int) $response->getHeaderLine('Content-Length') > self::MAX_BYTES) {
                                throw new RuntimeException('The page is too large to read.');
                            }
                        },
                    ])
                    ->get($url);
            } catch (Throwable) {
                throw new RuntimeException('The site did not answer, or the page is too large to read.');
            }

            if (! $response->redirect()) {
                if ($response->failed()) {
                    throw new RuntimeException("The site answered with an error ({$response->status()}). It may block automated reading or need a sign-in.");
                }

                return [$response, $url];
            }

            $location = $response->header('Location');

            if ($location === '') {
                throw new RuntimeException('The site redirected without saying where to.');
            }

            $url = $this->resolve($url, $location);
        }

        throw new RuntimeException('The page redirected too many times.');
    }

    /**
     * @return array{string, int, string}
     */
    private function publicTarget(string $url): array
    {
        $parts = parse_url($url);
        $scheme = mb_strtolower($parts['scheme'] ?? '');
        $host = mb_strtolower(mb_trim($parts['host'] ?? '', '[]'));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw new RuntimeException('Only http and https addresses can be read.');
        }

        $addresses = filter_var($host, FILTER_VALIDATE_IP) !== false ? [$host] : (gethostbynamel($host) ?: []);

        if ($addresses === []) {
            throw new RuntimeException('The site could not be found.');
        }

        foreach ($addresses as $address) {
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                throw new RuntimeException('Only public web pages can be read.');
            }
        }

        $address = $addresses[0];

        return [$host, $parts['port'] ?? ($scheme === 'https' ? 443 : 80), str_contains($address, ':') ? "[{$address}]" : $address];
    }

    private function resolve(string $base, string $location): string
    {
        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $location) === 1) {
            return $location;
        }

        $parts = parse_url($base);
        $origin = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '') . (isset($parts['port']) ? ":{$parts['port']}" : '');

        if (str_starts_with($location, '//')) {
            return ($parts['scheme'] ?? 'https') . ':' . $location;
        }

        if (str_starts_with($location, '/')) {
            return $origin . $location;
        }

        $path = $parts['path'] ?? '/';

        return $origin . mb_substr($path, 0, (int) mb_strrpos($path, '/') + 1) . $location;
    }

    private function meta(HTMLDocument $document, string $name): ?string
    {
        foreach ($document->querySelectorAll('meta[name], meta[property]') as $meta) {
            if (mb_strtolower($meta->getAttribute('name') ?? $meta->getAttribute('property') ?? '') === $name) {
                return $this->clean($meta->getAttribute('content'));
            }
        }

        return null;
    }

    /**
     * The schema.org JobPosting objects in the page's JSON-LD, which many job boards include even when the post itself
     * is rendered by JavaScript.
     *
     * @return list<array<mixed>>
     */
    private function jobPostings(HTMLDocument $document): array
    {
        $postings = [];

        foreach ($document->querySelectorAll('script[type="application/ld+json"]') as $script) {
            $data = json_decode($script->textContent ?? '', true);

            if (! is_array($data)) {
                continue;
            }

            $nodes = array_is_list($data) ? $data : (is_array($data['@graph'] ?? null) ? $data['@graph'] : [$data]);

            foreach ($nodes as $node) {
                if (is_array($node) && in_array('JobPosting', (array) ($node['@type'] ?? []), true)) {
                    if (is_string($node['description'] ?? null)) {
                        $node['description'] = $this->limit($this->htmlText($node['description']));
                    }

                    $postings[] = $node;
                }
            }
        }

        return $postings;
    }

    private function text(HTMLDocument $document): string
    {
        $body = $document->body;

        if (! $body instanceof Element) {
            return '';
        }

        foreach ($body->querySelectorAll('script, style, noscript, template, svg, iframe, nav, footer') as $hidden) {
            $hidden->remove();
        }

        foreach ($body->querySelectorAll(self::BLOCKS) as $block) {
            $block->before("\n");
            $block->after("\n");
        }

        foreach ($body->querySelectorAll('li') as $item) {
            $item->prepend('- ');
        }

        return $this->tidy($body->textContent ?? '');
    }

    private function htmlText(string $html): string
    {
        if ($html === strip_tags($html)) {
            return $this->tidy(html_entity_decode($html, ENT_QUOTES | ENT_HTML5));
        }

        return $this->text(HTMLDocument::createFromString("<!doctype html><body>{$html}</body>", LIBXML_NOERROR, 'UTF-8'));
    }

    private function tidy(string $text): string
    {
        $lines = array_map(fn (string $line): string => mb_trim((string) preg_replace('/[^\S\n]+/u', ' ', $line)), explode("\n", $text));

        return mb_trim((string) preg_replace("/\n{3,}/", "\n\n", implode("\n", $lines)));
    }

    private function limit(string $text): string
    {
        return Str::limit($text, self::MAX_TEXT, "\n[…the rest of the page was cut]");
    }

    private function clean(?string $value): ?string
    {
        $value = $value === null ? '' : $this->tidy($value);

        return $value === '' ? null : $value;
    }
}
