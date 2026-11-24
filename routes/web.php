<?php

use App\Http\Controllers\BackupController;
use App\Http\Controllers\PlaylistController;
use App\Http\Controllers\PlaylistMemberController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SongController;
use App\Http\Controllers\SingerController;
use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

Route::get('/', function () {
    // If the user is authenticated, redirect to dashboard, else show welcome page
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
});

// Language changement
Route::get('/lang/{locale}', function (string $locale) {
    if(in_array($locale, ['fr', 'en'])) {
        Session::put('locale', $locale);
    }
    return back();
})->name('lang.switch');

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
    // Adding scraped infos in the playlist
    Route::post('/playlists/{playlist}/songs/scraped', [SearchController::class, 'addScrapedInfos'])->name('playlists.songs.add-scraped');
    Route::post('/playlists/{playlist}/songs/import-xml', [SongController::class, 'importXml'])->name('playlists.songs.import-xml');
    // Deleting elements from the playlist
    Route::delete('/songs/{song}', [SongController::class, 'destroy'])->name('songs.destroy');
    Route::delete('/singers/{singer}', [SingerController::class, 'destroy'])->name('singers.destroy');

    // Other CRUD routes
    Route::resource('playlists',PlaylistController::class)->except(['index']);
    
    // Playlist member management
    Route::post('/playlists/{playlist}/members', [PlaylistMemberController::class, 'store'])->name('playlists.members.store');
    Route::delete('/playlists/{playlist}/members/{user}', [PlaylistMemberController::class, 'destroy'])->name('playlists.members.destroy');

    // Crypted document management
    Route::post('/playlists/{playlist}/documents', [DocumentController::class, 'store'])
    ->name('playlists.documents.store');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])
        ->name('documents.download');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])
        ->name('documents.destroy');

    // ZIP download of the database backup
    Route::post('/playlist/{playlist}/backup', [BackupController::class, 'download'])->name('playlists.backup');
});
require __DIR__.'/auth.php';
