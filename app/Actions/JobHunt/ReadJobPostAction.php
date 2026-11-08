<?php

declare(strict_types=1);

namespace App\Actions\JobHunt;

use App\Ai\Agents\JobPostReader;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Throwable;

final readonly class ReadJobPostAction
{
    private const array TEXT_FIELDS = ['company', 'role', 'country', 'location', 'salary'];

    /**
     * Pick the job form fields out of a job post. Every value is checked against what the form accepts, so a field
     * the model got wrong comes back empty rather than failing the form.
     *
     * @return array{company: string|null, role: string|null, country: string|null, location: string|null, salary: string|null, deadline: string|null, link: string|null, summary: string|null}
     */
    public function handle(string $post): array
    {
        $read = new JobPostReader()->prompt($post);

        $fields = [];

        foreach (self::TEXT_FIELDS as $field) {
            $fields[$field] = $this->text($read[$field] ?? null, 255);
        }

        return [
            ...$fields,
            'deadline' => $this->date($read['deadline'] ?? null),
            'link' => $this->link($read['link'] ?? null),
            'summary' => $this->text($read['summary'] ?? null, 2000),
        ];
    }

    private function text(mixed $value, int $limit): ?string
    {
        if (! is_string($value) || mb_trim($value) === '') {
            return null;
        }

        return Str::limit(mb_trim($value), $limit, '');
    }

    private function date(mixed $value): ?string
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', $value)?->format('Y-m-d') === $value ? $value : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function link(mixed $value): ?string
    {
        if (! is_string($value) || mb_strlen($value) > 2048 || filter_var($value, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true) ? $value : null;
    }
}
