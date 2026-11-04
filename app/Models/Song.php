<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Song extends Model
{
    const UPDATED_AT = 'updated_at';
    const CREATED_AT = 'added_at';

    protected $fillable= [
        'playlist_id',
        'song_name',
        'artist_name',
        'song_infos',
        'source_url',
        'added_at',
        'updated_at'
    ];

    /**
     * Get the playlist that owns the song.
     * @return BelongsTo
     */
    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
    }
}
