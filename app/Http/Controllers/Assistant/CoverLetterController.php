<?php

declare(strict_types=1);

namespace App\Http\Controllers\Assistant;

use App\Actions\Assistant\BuildCoverLetterPdfAction;
use App\Ai\Conversations;
use App\Models\CoverLetter;
use Illuminate\Http\Response;

final class CoverLetterController
{
    /**
     * Download a cover letter as a PDF.
     */
    public function show(int $coverLetter, BuildCoverLetterPdfAction $action): Response
    {
        $letter = CoverLetter::query()
            ->with('job')
            ->whereKey($coverLetter)
            ->whereIn('conversation_id', Conversations::of(authUser())->select('id'))
            ->firstOrFail();

        return response($action->handle($letter), headers: [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $action->fileName($letter) . '"',
        ]);
    }
}
