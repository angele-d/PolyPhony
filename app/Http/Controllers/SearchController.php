<?php

namespace App\Http\Controllers;

use App\Models\Playlist;
use App\Services\SongfactsScraper;
use App\Services\Song as SongService;
use App\Services\Singer as SingerService;
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
        $artist = $request->input('artist', '');
        $song = $request->input('song', '');
        $query = $request->input('q', '');
        $type = $request->input('type', 'song');

        if (empty(trim($artist)) && empty(trim($song)) && strlen(trim($query)) < 2) {
            return response()->json(['direct' => false, 'proposals' => []]);
        }

        $result = $this->scraper->searchProposals($artist, $song, $query, $type);
        return response()->json($result);
    }

    /**
     * Add a scraped infos in the specified playlist
     */
    public function addScrapedInfos(Request $request, Playlist $playlist)
    {
        $request->validate([
            'source_url' => 'required|url',
        ]);

        $path = parse_url($request->source_url, PHP_URL_PATH);
        $segments = array_values(array_filter(explode('/', $path ?? '')));

        // Case 1: URL format is /facts/artist-slug -> Singer
        if(count($segments) === 2 && $segments[0] === 'facts') {
            $singerService = new SingerService($this->scraper);
            $singerService->playlist_id = $playlist->id;
            $singerService->create($request->source_url);

            if (empty($singerService->singer_name)) {
                return back()->with('error', 'Impossible de récupérer les informations depuis Songfacts.');
            }

            $singerService->save();

            return back()->with('success', "La fiche de l'artiste \"{$singerService->singer_name}\" a été extraite et ajoutée avec succès !");
        }

        // Case 2: URL format is /facts/artist-slug/song-slug -> Song
        $songService = new SongService($this->scraper);
        $songService->playlist_id = $playlist->id;
        $songService->create($request->source_url);

        if (empty($songService->song_name)) {
            return back()->with('error', 'Impossible de récupérer les informations depuis Songfacts.');
        }

        $songService->save();

        return back()->with('success', "Le morceau \"{$songService->song_name}\" a été extrait et ajouté avec succès !");
    }
}