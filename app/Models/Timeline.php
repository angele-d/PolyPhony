<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Timeline extends Model
{
    use HasFactory;

    const CREATED_AT = 'added_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'user_id',
        'playlist_id',
        'artist_name',
        'event_year',
        'event_description',
        'source_url',
        'added_at',
        'updated_at'
    ];

    /**
     * Get the playlist that owns the timeline.
     * @return BelongsTo
     */
    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
    }
}
