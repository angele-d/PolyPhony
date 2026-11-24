<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Playlist;
use App\Services\FileEncryptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function __construct(private FileEncryptionService $encryptionService) {}

    /**
     * Validates, encrypts with OpenSSL, and stores a document (PDF, PNG, MP3).
     */
    public function store(Request $request, Playlist $playlist): RedirectResponse
    {
        Gate::authorize('contribute', $playlist);

        $request->validate([
            'document' => ['required', 'file', 'mimes:pdf,png,mp3,jpg', 'max:20480'], // Max 20 Mo
        ], [
            'document.required' => __('Please select a file to upload.'),
            'document.mimes'    => __('Only PDF, PNG, MP3 and JPG files are allowed.'),
            'document.max'      => __('The file must not exceed 20 MB.'),
        ]);

        $file = $request->file('document');
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());

        // Trustworthy determination of MIME type based on extension, with fallback to the file's detected MIME type.
        $mimeType = match ($extension) {
            'pdf'   => 'application/pdf',
            'png'   => 'image/png',
            'mp3'   => 'audio/mpeg',
            'jpg'  => 'image/jpeg',
            default => $file->getMimeType() ?: 'application/octet-stream',
        };

        // File content is read and encrypted using OpenSSL
        $rawContent = file_get_contents($file->getRealPath());
        $encryptedContent = $this->encryptionService->encrypt($rawContent);

        // Store the encrypted file on the private 'local' disk (outside the public folder)
        $storagePath = "encrypted_documents/playlist_{$playlist->id}/" . Str::uuid() . '.enc';
        Storage::disk('local')->put($storagePath, $encryptedContent);

        Document::create([
            'playlist_id'    => $playlist->id,
            'original_name'  => $originalName,
            'encrypted_path' => $storagePath,
            'mime_type'      => $mimeType,
        ]);

        return back()->with('success', __('The file ":name" has been encrypted and added to the playlist.', [
            'name' => $originalName,
        ]));
    }

    /**
     * Decrypts and returns the secured file (download or inline view).
     */
    public function download(Request $request, Document $document): Response
    {
        Gate::authorize('view', $document->playlist);

        if (!Storage::disk('local')->exists($document->encrypted_path)) {
            abort(404, __('The encrypted file could not be found on the server.'));
        }

        $encryptedPayload = Storage::disk('local')->get($document->encrypted_path);
        $decryptedContent = $this->encryptionService->decrypt($encryptedPayload);

        // 'inline' permet d'écouter le MP3 ou d'ouvrir le PDF/PNG dans le navigateur
        $disposition = $request->boolean('inline') ? 'inline' : 'attachment';
        $safeFilename = str_replace('"', '', $document->original_name);

        return response($decryptedContent, 200, [
            'Content-Type'        => $document->mime_type,
            'Content-Disposition' => "{$disposition}; filename=\"{$safeFilename}\"",
            'Content-Length'      => strlen($decryptedContent),
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    /**
     * Delete the encrypted file from disk and its database entry.
     */
    public function destroy(Document $document): RedirectResponse
    {
        Gate::authorize('contribute', $document->playlist);

        if (Storage::disk('local')->exists($document->encrypted_path)) {
            Storage::disk('local')->delete($document->encrypted_path);
        }

        $name = $document->original_name;
        $document->delete();

        return back()->with('success', __('The file ":name" has been deleted.', [
            'name' => $name,
        ]));
    }
}