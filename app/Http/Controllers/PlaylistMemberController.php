<?php

namespace App\Http\Controllers;

use App\Models\Playlist;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PlaylistMemberController extends Controller
{
    /**
     * Invite a user as a contributor via their email address.
     */
    public function store(Request $request, Playlist $playlist): RedirectResponse
    {
        Gate::authorize('manageMembers', $playlist);

        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.exists' => 'Aucun compte utilisateur n\'est associé à cette adresse e-mail.',
        ]);

        $userToAdd = User::where('email', $validated['email'])->first();

        if ((int) $userToAdd->id === (int) $playlist->administrator_id) {
            return back()->with('error', 'Vous êtes déjà l\'administrateur de cette playlist.');
        }

        if ($playlist->contributors()->where('user_id', $userToAdd->id)->exists()) {
            return back()->with('error', "{$userToAdd->name} est déjà contributeur sur cette playlist.");
        }

        $playlist->contributors()->attach($userToAdd->id, [
            'added_at' => now(),
        ]);

        return back()->with('success', "{$userToAdd->name} a été ajouté(e) à l'équipe de contribution.");
    }

    /**
     * Kick a contributor out of the playlist.
     */
    public function destroy(Playlist $playlist, User $user): RedirectResponse
    {
        Gate::authorize('manageMembers', $playlist);

        $playlist->contributors()->detach($user->id);

        return back()->with('success', "{$user->name} a été retiré(e) de la playlist.");
    }
}