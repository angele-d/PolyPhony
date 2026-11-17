<?php

namespace App\Services;

use App\Models\Singer as SingerModel;

class Singer
{
    public ?int $playlist_id = null;
    public ?string $singer_name = null;
    public ?string $bio_summary = null;
    public ?string $picture_url = null;
    public ?string $source_url = null;

    public function __construct(private SongfactsScraper $scraper) {}

    public function create(string $url): self
    {
        $this->source_url = $url;

        $path = parse_url($url, PHP_URL_PATH);
        $segments = array_values(array_filter(explode('/', $path ?? '')));

        // URL type : /facts/{artistSlug}
        if (count($segments) === 2 && $segments[0] === 'facts') {
            $data = $this->scraper->scrapeArtistFacts($segments[1]);
            if ($data) {
                $this->singer_name = $data['singer_name'];
                $this->bio_summary = $data['bio_summary'];
                $this->picture_url = $data['picture_url'];
                $this->source_url  = $data['source_url'] ?? $url;
            }
        }

        return $this;
    }

    public function save(): SingerModel
    {
        return SingerModel::create([
            'playlist_id' => $this->playlist_id,
            'singer_name' => $this->singer_name,
            'bio_summary' => $this->bio_summary,
            'picture_url' => $this->picture_url,
            'source_url'  => $this->source_url,
        ]);
    }
}