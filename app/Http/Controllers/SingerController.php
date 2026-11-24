<?php

namespace App\Http\Controllers;

use App\Models\Singer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SingerController extends Controller
{
    public function destroy(Singer $singer): RedirectResponse
    {
        Gate::authorize('contribute', $singer->playlist);

        $singerName = $singer->singer_name;
        $singer->delete();

        return back()->with('success', __('The artist ":name" has been deleted from the playlist.', [
            'name' => $singerName,
        ]));
    }
}