<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portfolio;

use App\Actions\Portfolio\BuildPlainTextResumeAction;
use App\Actions\Portfolio\ResolveResumeAction;
use App\Models\Resume;
use Illuminate\Http\Response;

final readonly class PlainTextResumeController
{
    public function __construct(
        private ResolveResumeAction $resolveResume,
        private BuildPlainTextResumeAction $buildPlainTextResume,
    ) {}

    /**
     * Show the resume as plain text.
     */
    public function show(): Response
    {
        $content = $this->buildPlainTextResume->handle($this->resolveResume->handle(Resume::public()));

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
