<?php

namespace App\Http\Controllers;

use App\Models\Song;
use App\Models\Playlist;
use App\Services\XmlSongImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Throwable;

class SongController extends Controller
{

    public function importXml(Request $request, Playlist $playlist, XmlSongImportService $importer): RedirectResponse
    {
        Gate::authorize('contribute', $playlist);

        $request->validate([
            'xml_file' => ['required', 'file', 'extensions:xml', 'max:5120'], // Max 5 Mb
        ], [
            'xml_file.required'   => 'Please select a XML file to import.',
            'xml_file.extensions' => 'The file must have the .xml extension.',
            'xml_file.max'        => 'The XML file must not exceed 5 MB.',
        ]);

        try {
            $content = file_get_contents($request->file('xml_file')->getRealPath());
            $stats = $importer->importFromString($content, $playlist);

            if ($stats['imported'] === 0) {
                return back()->with('error', 'No valid songs were found in the XML file. Please ensure that the file contains <song> elements with both a title and an artist.');
            }

            $message = "{$stats['imported']} song(s) imported successfully from the XML file";
            if ($stats['skipped'] > 0) {
                $message .= " ({$stats['skipped']} uncomplete song(s) skipped due to missing title or artist.)";
            }

            return back()->with('success', $message);
        } catch (Throwable $e) {
            return back()->with('error', 'Error importing XML: ' . $e->getMessage());
        }
    }

    public function destroy(Song $song): RedirectResponse{
        Gate::authorize('contribute', $song->playlist);

        $songName = $song->song_name;
        $song->delete();

        return back()->with('success', "The song \"{$songName}\" has been successfully deleted from the playlist.");
    }
}
