<?php

declare(strict_types=1);

namespace App\Ai;

use App\Models\Resume;

/**
 * The resume the owner is editing while they talk to the assistant: the saved
 * resume, the unsaved draft the editor shows, and how its preview lays it out.
 */
final readonly class ResumeEditorContext
{
    public function __construct(
        public Resume $resume,
        public Resume $draft,
        public ?int $previewPages = null,
        public ?float $previewLastPageFill = null,
    ) {}

    /**
     * The resume to compare a change against: the editor's draft when the change is to the resume being edited.
     */
    public static function baseFor(Resume $resume, ?self $editor): Resume
    {
        return $editor instanceof self && $editor->resume->is($resume) ? $editor->draft : $resume;
    }

    public function isEditing(Resume $resume): bool
    {
        return $this->resume->is($resume);
    }
}
