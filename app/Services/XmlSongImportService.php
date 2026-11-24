<?php

namespace App\Services;

use App\Models\Playlist;
use App\Models\Song;
use RuntimeException;
use SimpleXMLElement;

class XmlSongImportService
{
    /**
     * Parse an XML content in the format <songs><song>...</song></songs>
     * and adds valid songs to the target playlist.
     * @param string $xmlContent The XML content to parse
     * @param Playlist $playlist The target playlist to add songs to
     * @return array{imported: int, skipped: int}
     */
    public function importFromString(string $xmlContent, Playlist $playlist): array
    {
        libxml_use_internal_errors(true);
        libxml_clear_errors();

        $xml = simplexml_load_string(
            $xmlContent,
            SimpleXMLElement::class,
            LIBXML_NONET | LIBXML_NOCDATA
        );

        if ($xml === false || $xml->getName() !== 'songs') {
            libxml_clear_errors();
            throw new RuntimeException(__('The provided XML content is invalid or does not contain a <songs> root element.'));
        }

        if (count($xml->song) === 0) {
            throw new RuntimeException(__('The provided XML content does not contain any <song> elements.'));
        }

        $imported = 0;
        $skipped = 0;

        foreach ($xml->song as $songNode) {
            $songName   = trim((string) ($songNode->songname ?? ''));
            $artistName = trim((string) ($songNode->artistname ?? ''));
            $rawInfos   = trim((string) ($songNode->songinfos ?? ''));
            $sourceUrl  = trim((string) ($songNode->songurl ?? ''));

            // A song must have a title and an artist to be valid
            if ($songName === '' || $artistName === '') {
                $skipped++;
                continue;
            }

            // Clean the URL if provided, and set to null if it's not a valid URL
            if ($sourceUrl !== '' && !filter_var($sourceUrl, FILTER_VALIDATE_URL)) {
                $sourceUrl = null;
            }

            // Coherent formatting with the bullet point display in the playlist
            $formattedInfos = $rawInfos !== ''
                ? '• ' . preg_replace('/\s+/', ' ', $rawInfos)
                : __('No data available');

            Song::create([
                'playlist_id' => $playlist->id,
                'song_name'   => $songName,
                'artist_name' => $artistName,
                'song_infos'  => $formattedInfos,
                'source_url'  => $sourceUrl ?: null,
            ]);

            $imported++;
        }

        return [
            'imported' => $imported,
            'skipped'  => $skipped,
        ];
    }
}