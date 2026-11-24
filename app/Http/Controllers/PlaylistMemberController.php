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
            'email.exists' => __('No user account is associated with this email address.'),
        ]);

        $userToAdd = User::where('email', $validated['email'])->first();

        if ((int) $userToAdd->id === (int) $playlist->administrator_id) {
            return back()->with('error', __('You are already the administrator of this playlist.'));
        }

        if ($playlist->contributors()->where('user_id', $userToAdd->id)->exists()) {
            return back()->with('error', __(':name is already a contributor to this playlist.', [
                'name' => $userToAdd->name,
            ]));
        }

        $playlist->contributors()->attach($userToAdd->id, [
            'added_at' => now(),
        ]);

        return back()->with('success', __(':name has been added to the contributor team.', [
            'name' => $userToAdd->name,
        ]));
    }

    /**
     * Kick a contributor out of the playlist.
     */
    public function destroy(Playlist $playlist, User $user): RedirectResponse
    {
        Gate::authorize('manageMembers', $playlist);

        $playlist->contributors()->detach($user->id);

        return back()->with('success', __(':name has been removed from the playlist.', [
            'name' => $user->name,
        ]));
    }
}