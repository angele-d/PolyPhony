<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
