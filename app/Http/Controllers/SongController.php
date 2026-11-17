<?php

namespace App\Http\Controllers;

use App\Models\Song;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SongController extends Controller
{
    public function destroy(Song $song): RedirectResponse{
        Gate::authorize('contribute', $song->playlist);

        $songName = $song->song_name;
        $song->delete();

        return back()->with('success', "The song \"{$songName}\" has been successfully deleted from the playlist.");
    }
}
