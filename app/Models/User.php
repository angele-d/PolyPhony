<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's playlists.(Role: Administrator)
     * @return HasMany
     */
    public function playlists(): HasMany
    {
        return $this->hasMany(Playlist::class, 'administrator_id');
    }

    /**
     * Get the playlists the user has contributed to. (Role: Contributor)
     * @return BelongsToMany
     */
    public function contributedPlaylists(): BelongsToMany
    {
        return $this->belongsToMany(Playlist::class, 'playlist_user')
                ->withPivot('added_at');
    }
}
