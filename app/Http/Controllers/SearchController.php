<?php

namespace App\Http\Controllers;

use App\Models\Playlist;
use App\Services\SongfactsScraper;
use App\Services\Song as SongService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function __construct(private SongfactsScraper $scraper) {}

    /**
     * Endpoint API for toolbar auto-completion
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        if (strlen($query) < 2) {
            return response()->json(['direct' => false, 'proposals' => []]);
        }

        $result = $this->scraper->searchProposals($query);
        return response()->json($result);
    }

    /**
     * Add a scraped song in the specified playlist
     */
    public function addScrapedSong(Request $request, Playlist $playlist)
    {
        $request->validate([
            'source_url' => 'required|url',
        ]);

        $songService = new SongService($this->scraper);
        $songService->playlist_id = $playlist->id;
        $songService->create($request->source_url);

        if (empty($songService->song_name)) {
            return back()->with('error', 'Impossible de récupérer les informations de ce morceau.');
        }

        $songService->save();

        return back()->with('success', "Le morceau \"{$songService->song_name}\" a été extrait et ajouté avec succès !");
    }
}