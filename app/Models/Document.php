<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasFactory;

    const CREATED_AT = 'added_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'playlist_id',
        'original_name',
        'encrypted_path',
        'mime_type',
        'added_at',
    ];

    /**
     * Get the playlist that owns the document.
     * @return BelongsTo
     */
    public function playlist(): BelongsTo{
        return $this->belongsTo(Playlist::class);
    }
}
