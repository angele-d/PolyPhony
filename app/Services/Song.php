<?php

namespace App\Services;

use App\Contracts\iSongRepository;
use App\Models\Song as SongModel;

use Illuminate\Support\Facades\DB;
use simplehtmldom\HtmlWeb;

class Song implements iSongRepository{
    public string $song_name = '';
    public string $song_infos = '';
    public string $song_url = '';
    public string $artist_name = '';
    public string $added_at = '';

    public function create(string $url): self{

        $this->song_url = $url;
        // scraper SimpleHTMLDom will be applied here
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
            'song_name' => $this->song_name,
            'artist_name' => $this->artist_name,
            'song_infos' => $this->song_infos,
            'song_url' => $this->song_url,
            'added_at' => $this->added_at,
        ]);
        /*
        return DB::table('songs')->insert([
            'song_name' => $this->song_name,
            'artist_name' => $this->artist_name,
            'song_infos' => $this->song_infos,
            'song_url' => $this->song_url,
            'added_at' => $this->added_at,
            'updated_at' => now()->toDateTimeString(),
        ]);*/
    }

    public function read(): array{
        return SongModel::all()->toArray();
        //return DB::table('songs')->get()->toArray();
    }

    public function update(int $id, array $data): bool{
        $song = SongModel::find($id);
        return $song ? $song->update($data) : false;
        /*$data['updated_at'] = now()->toDateTimeString();
        return DB::table('songs')->where('id', $id)->update($data);*/
    }

    public function delete(int $id): bool{
        return (bool) SongModel::destroy($id);
        //return DB::table('songs')->where('id', $id)->delete();
    }
}