<?php

use App\Http\Controllers\PlaylistController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard = Playlists index
    Route::get('/dashboard', [PlaylistController::class, 'index'])->name('dashboard');
    // playlists.index redirection into dashboard
    Route::get('/playlists', function () {
        return redirect()->route('dashboard');
    })->name('playlists.index');

    // Profile edition
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Dynamic autocompletion for Songfacts
    Route::get('/search/songfacts', [SearchController::class, 'autocomplete'])->name('search.songfacts');

    // Add a scraped song in the playlist
    Route::post('/playlists/{playlist}/songs/scraped', [SearchController::class, 'addScrapedSong'])->name('playlists.songs.add-scraped');

    // Other CRUD routes
    Route::resource('playlists',PlaylistController::class)->except(['index']);
});
require __DIR__.'/auth.php';
