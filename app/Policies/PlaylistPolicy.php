<?php

namespace App\Policies;

use App\Models\Playlist;
use App\Models\User;

class PlaylistPolicy
{
    /**
     * Check if the user is the creator / administrator of the playlist.
     */
    private function isAdmin(User $user, Playlist $playlist): bool
    {
        return (int) $playlist->administrator_id === (int) $user->id;
    }

    /**
     * Check if the user is a contributor of the playlist.
     */
    private function isContributor(User $user, Playlist $playlist): bool
    {
        return $playlist->contributors()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if the user can view the playlist.
     * For contributor and creator
     */
    public function view(User $user, Playlist $playlist): bool
    {
        return $this->isAdmin($user, $playlist) || $this->isContributor($user, $playlist);
    }

    /**
     * Check if the user can update the playlist.
     * Only the creator / administrator can update the playlist.
     */
    public function update(User $user, Playlist $playlist): bool
    {
        return $this->isAdmin($user, $playlist);
    }

    /**
     * Check if the user can delete the playlist
     * Only the creator / administrator can delete the playlist.
     */
    public function delete(User $user, Playlist $playlist): bool
    {
        return $this->isAdmin($user, $playlist);
    }

    /**
     * Invitation and removal of contributors
     * Only the creator / administrator can manage contributors.
     */
    public function manageMembers(User $user, Playlist $playlist): bool
    {
        return $this->isAdmin($user, $playlist);
    }

    /**
     * Check if the user can contribute to the playlist. (adding, editing or deleting content: songs, singers, timelines, documents).
     * For contributor and creator
     */
    public function contribute(User $user, Playlist $playlist): bool
    {
        return $this->isAdmin($user, $playlist) || $this->isContributor($user, $playlist);
    }
}