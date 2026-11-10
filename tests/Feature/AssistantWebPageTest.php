<?php

declare(strict_types=1);

use App\Ai\Tools\ReadWebPage;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Tools\Request;

const JOB_PAGE = 'https://93.184.216.34/jobs/42';

beforeEach(function (): void {
    Http::preventStrayRequests();
});

function readWebPage(string $url): string
{
    return resolve(ReadWebPage::class)->handle(new Request(['url' => $url]));
}

test('a job post page is read as its title, job posting data and visible text', function (): void {
    $posting = json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            ['@type' => 'Organization', 'name' => 'Acme'],
            ['@type' => 'JobPosting', 'title' => 'Backend Engineer', 'description' => '<p>Build <b>APIs</b> &amp; services.</p>'],
        ],
    ]);

    Http::fake([JOB_PAGE => Http::response(<<<HTML
        <!doctype html>
        <html><head>
            <title>Backend Engineer at Acme</title>
            <meta name="description" content="Join Acme as a backend engineer.">
            <script type="application/ld+json">{$posting}</script>
            <style>body { color: red }</style>
        </head><body>
            <nav>Home Jobs About</nav>
            <h1>Backend Engineer</h1>
            <p>Remote   in the EU.</p>
            <ul><li>PHP</li><li>Laravel</li></ul>
            <script>trackVisitor()</script>
        </body></html>
        HTML, 200, ['Content-Type' => 'text/html; charset=utf-8'])]);

    $page = readWebPage(JOB_PAGE);

    expect($page)
        ->toContain('Address: ' . JOB_PAGE)
        ->toContain('Title: Backend Engineer at Acme')
        ->toContain('Description: Join Acme as a backend engineer.')
        ->toContain('"title":"Backend Engineer","description":"Build APIs & services."')
        ->toContain("Backend Engineer\n\nRemote in the EU.\n\n- PHP\n\n- Laravel")
        ->not->toContain('Organization')
        ->not->toContain('Home Jobs About')
        ->not->toContain('trackVisitor')
        ->not->toContain('color: red');
});

test('only public web addresses are read', function (string $url): void {
    expect(readWebPage($url))->toStartWith('The page could not be read:');

    Http::assertNothingSent();
})->with([
    'loopback' => 'http://127.0.0.1/admin',
    'private network' => 'http://10.0.0.5/',
    'cloud metadata' => 'http://169.254.169.254/latest/meta-data',
    'localhost name' => 'http://localhost:8000/',
    'IPv6 loopback' => 'http://[::1]/',
    'not http' => 'file:///etc/passwd',
]);

test('a redirect is followed only to a public address', function (): void {
    Http::fake([
        JOB_PAGE => Http::response('', 302, ['Location' => '/jobs/42-backend-engineer']),
        'https://93.184.216.34/jobs/42-backend-engineer' => Http::response('', 301, ['Location' => 'http://127.0.0.1/internal']),
    ]);

    expect(readWebPage(JOB_PAGE))->toBe('The page could not be read: Only public web pages can be read. Ask the owner to paste the text of the page instead.');

    Http::assertSentCount(2);
    Http::assertNotSent(fn (HttpRequest $request): bool => str_contains($request->url(), '127.0.0.1'));
});

test('a page that cannot be read tells the assistant why', function (int $status, string $type, string $body, string $message): void {
    Http::fake([JOB_PAGE => Http::response($body, $status, ['Content-Type' => $type])]);

    expect(readWebPage(JOB_PAGE))->toContain($message);
})->with([
    'blocked' => [403, 'text/html', 'Forbidden', 'The site answered with an error (403).'],
    'a file' => [200, 'application/pdf', '%PDF', 'The page is a application/pdf file, not a web page.'],
    'built by JavaScript' => [200, 'text/html', '<html><body><div id="app"></div><script>boot()</script></body></html>', 'The page has no readable text.'],
]);
