<?php

namespace App\Services;

use App\Contracts\iSongRepository;
use App\Models\Song as SongModel;

class Song implements iSongRepository{
    public string $song_name = '';
    public string $song_infos = '';
    public string $song_url = '';
    public string $artist_name = '';
    public int $playlist_id;

    public function __construct(private ?SongfactsScraper $scraper = null){
        $this->scraper = $scraper ?? new SongfactsScraper();
    }

    public function create(string $url): self{
        $this->song_url = $url;

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
            }
        }

        return $this;
        /*
        $doc = new HtmlWeb();
        $html = $doc->load($url);

        $this->song_url = $url;
        $this->added_at = now()->toDateTimeString();

        if($html){
            $this->song_name = trim($html->find('h1', 0)->plaintext ?? 'Unknown');
            $this->song_infos = trim($html->find('.songfact-text', 0)->plaintext ?? 'Unknown');
            
            // Extraction of the artist name from the HTML or URL
            $artistNode = $html->find('.artist-name', 0) ?? $html->find('h2', 0);
            if($artistNode){
                $this->artist_name = trim($artistNode->plaintext);
            } else {
                $pathSegments = explode('/', parse_url($url, PHP_URL_PATH));
                // In "songfacts.com/facts/artist-name/song-name", the artist name is in the 3rd segment (index 2)
                $this->artist_name = isset($pathSegments[2]) ? ucfirst(str_replace('-', ' ', $pathSegments[2])) : 'Unknown';
            }
        }

        return $this;
        */
    }

    public function save(): bool{
        return (bool) SongModel::create([
            'playlist_id' => $this->playlist_id,
            'song_name' => $this->song_name,
            'artist_name' => $this->artist_name,
            'song_infos' => $this->song_infos,
            'song_url' => $this->song_url,
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