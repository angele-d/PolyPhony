<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Singer extends Model
{
    use HasFactory;

    const CREATED_AT = 'added_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'playlist_id',
        'singer_name',
        'bio_summary',
        'picture_url',
        'source_url',
        'added_at',
        'updated_at'
    ];

    /**
     * Get the playlist that owns the singer.
     * @return BelongsTo
     */
    public function playlist(): BelongsTo{
        return $this->belongsTo(Playlist::class);
    }
}
