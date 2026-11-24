<?php

namespace App\Http\Controllers;

use App\Models\Playlist;
use App\Services\SongfactsScraper;
use App\Services\Song as SongService;
use App\Services\Singer as SingerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

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
     * Add scraped infos in the specified playlist
     */
    public function addScrapedInfos(Request $request, Playlist $playlist)
    {
        Gate::authorize('contribute', $playlist);

        $request->validate([
            'source_url' => 'required|url',
        ]);

        $path = parse_url($request->source_url, PHP_URL_PATH);
        $segments = array_values(array_filter(explode('/', $path ?? '')));

        // Case 1: URL format is /facts/artist-slug -> Singer
        if (count($segments) === 2 && $segments[0] === 'facts') {
            $singerService = new SingerService($this->scraper);
            $singerService->playlist_id = $playlist->id;
            $singerService->create($request->source_url);

            if (empty($singerService->singer_name)) {
                return back()->with('error', __('Unable to retrieve information from Songfacts.'));
            }

            $singerService->save();

            return back()->with('success', __('The artist profile ":name" has been successfully extracted and added!', [
                'name' => $singerService->singer_name,
            ]));
        }

        // Case 2: URL format is /facts/artist-slug/song-slug -> Song
        $songService = new SongService($this->scraper);
        $songService->playlist_id = $playlist->id;
        $songService->create($request->source_url);

        if (empty($songService->song_name)) {
            return back()->with('error', __('Unable to retrieve information from Songfacts.'));
        }

        $songService->save();

        return back()->with('success', __('The song ":name" has been successfully extracted and added!', [
            'name' => $songService->song_name,
        ]));
    }
}