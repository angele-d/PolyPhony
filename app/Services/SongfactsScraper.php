<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

use simplehtmldom\HtmlDocument;

class SongfactsScraper{
    private string $baseURL = 'https://www.songfacts.com';

    /**
     * cURL request with a user agent to fetch the content of a given URL.
     * @param string $url
     * @return string|null
     */
    public function fetchURLContent(string $url): ?string{
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification for simplicity

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);

        Log::info("Scraper Debug", [
        'url'         => $url,
        'http_code'   => $httpCode,
        'curl_error'  => $curlErr,
        'html_length' => strlen($response ?: ''),
        ]);

        return ($httpCode === 200 && $response !== false) ? $response : null;
    }

    /**
     * Scraper of SongFacts for Songs with Artists.
     * @param string $artistSlug
     * @param string $songSlug
     * @return array|null
     */
    public function scrapeSongFacts(string $artistSlug, string $songSlug): ?array{
        $url = "{$this->baseURL}/facts/{$artistSlug}/{$songSlug}";
        $htmlContent = $this->fetchURLContent($url);

        if (!$htmlContent)
            return null;


        // str_get_html is a function from the simplehtmldom library
        $dom = new HtmlDocument();
        $dom->load($htmlContent);

        // Extract title
        $titleNode = $dom->find('h1', 0);
        $title = $titleNode ? trim(strip_tags($titleNode->plaintext)) : Str::title(str_replace('-',' ',$songSlug));

        // Extract text/explications
        $facts = [];
        $factNodes = $dom->find('ul.songfacts-results li');

        if (!empty($factNodes)) {
            foreach ($factNodes as $li) { // Each fact = <li>...</li>
                $inner = $li->find('.inner', 0); // Keep the <div class="inner">...</div> content
                if ($inner) {
                    // Remove unwanted tags (div, span, a) from the inner content
                    foreach ($inner->find('div') as $div) 
                        $div->outertext = '';
                    foreach($inner->find('span') as $span) 
                        $span->outertext = '';
                    foreach($inner->find('a') as $a) 
                        $a->outertext = '';
                    
                    $rawHTML = $inner->outertext;
                    $text = trim(strip_tags($rawHTML));
                    
                    // Cleanup of HTML entities and whitespace
                    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $text = preg_replace('/\s+/', ' ', $text);

                    if(strlen($text)>20)
                        $facts[] = $text;
                }
            }
        }

        $formattedInfos = implode("\n\n", array_map(function ($fact){
            return "• " . trim($fact);
        }, $facts));

        $data = [
            'song_name'   => $title,
            'artist_name' => Str::title(str_replace('-', ' ', $artistSlug)),
            'song_infos'  => $formattedInfos ?: 'No data available',
            'source_url'  => $url,
        ];

        Log::info("Scrape Final Data", $data);

        return $data;
    }

    /**
     * Search engine and Songfacts suggestions (fallback if correspondance)
     * @param string $query
     * @return array
     */
    public function searchProposals(string $query): array{
        $slugQuery = Str::slug($query);

        // Try to resolve directly if "Artist Song" format
        $parts = explode('-', $slugQuery);
        if(count($parts) >= 2){
            $artistSlug = $parts[0];
            $songSlug = implode('-', array_slice($parts, 1));

            $directData = $this->scrapeSongFacts($artistSlug,$songSlug);
            if($directData)
                return[
                'direct' => true,
                'data' => $directData,
                ];
        }

        // Fallback: research results scraping
        $searchUrl = "{$this->baseURL}/search/songs/" . urlencode($query);
        $htmlContent = $this->fetchURLContent($searchUrl);
        $proposals = [];

        if($htmlContent){
            $dom = new HtmlDocument();
            $dom->load($htmlContent);
            foreach($dom->find('ul.browse-list-music li a, .search-results a') as $link){
                $href = $link->href;
                if(str_contains($href, '/facts/')){
                    $proposals[] = [
                        'title' => trim(strip_tags($link->plaintext)),
                        'url' => str_starts_with($href,'http') ? $href : $this->baseURL . $href,
                    ];
                }
            }
        }

        return [
            'direct' => false,
            'proposals' => array_values(array_unique($proposals, SORT_REGULAR)),
        ];
    }
}