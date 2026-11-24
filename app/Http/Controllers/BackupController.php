<?php

namespace App\Http\Controllers;

use App\Models\Playlist;
use App\Services\DatabaseBackupService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Throwable;

class BackupController extends Controller
{
    public function __construct(private DatabaseBackupService $backupService) {}

    /**
     * Generate the SQL backup (.txt compressed into .zip) and download it.
     */
    public function download(Playlist $playlist)
    {
        Gate::authorize('view', $playlist);

        try {
            $zipPath = $this->backupService->generatePlaylistBackup($playlist);

            return response()->download($zipPath, basename($zipPath), [
                'Content-Type' => 'application/zip',
            ]);
        } catch (Throwable $e) {
            Log::error('Error during database backup : ' . $e->getMessage());

            return back()->with('error', __('Failed to generate backup: :message', [
                'message' => $e->getMessage(),
            ]));
        }
    }
}