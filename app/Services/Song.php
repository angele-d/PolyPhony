<?php

namespace App\Services;

use App\Contracts\iSongRepository;
use App\Models\Song as SongModel;

class Song implements iSongRepository{
    public string $song_name = '';
    public string $song_infos = '';
    public string $source_url = '';
    public string $artist_name = '';
    public int $playlist_id;

    public function __construct(private ?SongfactsScraper $scraper = null){
        $this->scraper = $scraper ?? new SongfactsScraper();
    }

    /**
     * Add a new entry to the database by scraping the song facts from the provided URL.
     *
     * @param string $url
     * @return self
     */
    public function create(string $url): self{
        $this->source_url = $url;

        // Slug extraction from URL 
        $path = parse_url($url, PHP_URL_PATH);
        $segments = array_values(array_filter(explode('/', $path)));

        if (count($segments) >= 3 && $segments[0] === 'facts') {
            $artistSlug = $segments[1];
            $songSlug = $segments[2];

            $data = $this->scraper->scrapeSongFacts($artistSlug, $songSlug);
            if ($data) {
                $this->song_name   = $data['song_name'];
                $this->artist_name = $data['artist_name'];
                $this->song_infos  = $data['song_infos'];
                $this->source_url   = $data['source_url'];
            }
        }

        return $this;
    }

    public function save(): bool{
        return (bool) SongModel::create([
            'playlist_id' => $this->playlist_id,
            'song_name' => $this->song_name,
            'artist_name' => $this->artist_name,
            'song_infos' => $this->song_infos,
            'source_url' => $this->source_url,
        ]);
    }

    public function read(): array{
        return SongModel::all()->toArray();
    }

    public function update(int $id, array $data): bool{
        $song = SongModel::find($id);
        return $song ? $song->update($data) : false;
    }

    public function delete(int $id): bool{
        return (bool) SongModel::destroy($id);
    }
}