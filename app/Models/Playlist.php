<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Playlist extends Model
{
    // HasFactory trait allows the model to be used with factories for testing and seeding.
    use HasFactory;

    const CREATED_AT = 'added_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'playlist_name',
        'administrator_id',
        'description',
        'added_at',
    ];

/* === Users === */

    /**
     * Get the administrator of the playlist.
     * @return BelongsTo
     */
    public function administrator(): BelongsTo{
        return $this->belongsTo(User::class, 'administrator_id');
    }

    /**
     * Get the contributors invited in the playlist.
     * @return BelongsToMany
     */
    public function contributors(): BelongsToMany{
        return $this->belongsToMany(User::class, 'playlist_user')
                    ->withPivot('added_at');
    }

/* === Content === */

    /**
     * Get the songs in the playlist.
     * @return HasMany
     */
    public function songs(): HasMany{
        return $this->hasMany(Song::class);
    }

    /**
     * Get the singers in the playlist.
     * @return HasMany
     */
    public function singers(): HasMany{
        return $this->hasMany(Singer::class);
    }

    /**
     * Get the timelines in the playlist.
     * @return HasMany
     */
    public function timelines(): HasMany{
        return $this->hasMany(Timeline::class);
    }

    /**
     * Get the documents in the playlist.
     * @return HasMany
     */
    public function documents(): HasMany{
        return $this->hasMany(Document::class);
    }
}
