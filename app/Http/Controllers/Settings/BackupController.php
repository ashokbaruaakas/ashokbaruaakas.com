<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Backup\ExportBackupAction;
use App\Actions\Backup\ImportBackupAction;
use App\DTOs\PageMeta;
use App\Http\Requests\Settings\BackupImportRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class BackupController
{
    /**
     * Show the backup page.
     */
    public function show(): Response
    {
        return vue('settings/Backup', [], new PageMeta(
            title: 'Backup',
        ));
    }

    /**
     * Download everything as a zip file.
     */
    public function download(ExportBackupAction $action): BinaryFileResponse|RedirectResponse
    {
        try {
            $path = $action->handle();
        } catch (RuntimeException $exception) {
            toastError($exception->getMessage());

            return back();
        }

        return response()
            ->download($path, 'portfolio-backup-' . now()->format('Y-m-d-His') . '.zip', ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend();
    }

    /**
     * Replace everything with the contents of a backup.
     */
    public function store(BackupImportRequest $request, ImportBackupAction $action): RedirectResponse
    {
        /** @var UploadedFile $file */
        $file = $request->file('file');

        $action->handle((string) $file->getRealPath());

        toastSuccess('Backup imported.');

        return back();
    }
}
