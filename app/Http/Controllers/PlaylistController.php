<?php

namespace App\Http\Controllers;

use App\Models\Playlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlaylistController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\Response
     */
    public function index(){
        /**@var \App\Models\User $user */
        $user = Auth::user();

        // Playlists where the user is the administrator
        $createdPlaylists = $user->playlists()->with('administrator')->latest('added_at')->get();

        // Playlists where the user is a contributor
        $contributedPlaylists = $user->contributedPlaylists()->with('administrator')->latest('added_at')->get();

        return view('dashboard', compact('createdPlaylists', 'contributedPlaylists'));
    }

    /**
     * Show the form for creating a new resource.
     * @return \Illuminate\Http\Response
     */
    public function create(){
        return view('playlists.create');
    }

    /**
     * Store a newly created resource in storage.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request){
        $validated = $request->validate([
            'playlist_name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        
        /**@var \App\Models\User $user */
        $user = Auth::user();
        $playlist = $user->playlists()->create($validated);

        return redirect()->route('playlists.index')->with('success', 'Playlist created successfully.');
    }

    public function show(Playlist $playlist){
        // Ensure the user has access to the playlist
        if ($playlist->administrator_id !== Auth::id() && !$playlist->contributors->contains(Auth::id())) {
            abort(403, 'Unauthorized action.');
        }

        $playlist->load(['administrator', 'contributors', 'songs', 'singers', 'timelines','documents']);
        return view('playlists.show', compact('playlist'));
    }
}
