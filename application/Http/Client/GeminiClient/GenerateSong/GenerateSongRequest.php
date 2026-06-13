<?php

declare(strict_types=1);

namespace Application\Http\Client\GeminiClient\GenerateSong;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

final readonly class GenerateSongRequest
{
    public function __construct(
        private string $songName,
        private string $language,
        private ?string $agencyName = null,
        private ?string $groupName = null,
        private ?string $talentName = null,
    ) {
    }

    public function songName(): string
    {
        return $this->songName;
    }

    public function language(): string
    {
        return $this->language;
    }

    public function agencyName(): ?string
    {
        return $this->agencyName;
    }

    public function groupName(): ?string
    {
        return $this->groupName;
    }

    public function talentName(): ?string
    {
        return $this->talentName;
    }

    public function toPsrRequest(
        RequestInterface $request,
        StreamFactoryInterface $streamFactory,
    ): RequestInterface {
        $body = json_encode($this->buildRequestBody(), JSON_THROW_ON_ERROR);

        return $request
            ->withMethod('POST')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($streamFactory->createStream($body));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRequestBody(): array
    {
        return [
            'tools' => [
                [
                    'google_search' => (object) [],
                ],
            ],
            'contents' => [
                [
                    'parts' => [
                        ['text' => $this->buildPrompt()],
                    ],
                ],
            ],
        ];
    }

    private function buildPrompt(): string
    {
        $outputLanguage = match ($this->language) {
            'ja' => 'Japanese',
            'ko' => 'Korean',
            default => 'English',
        };

        $affiliationContext = $this->buildAffiliationContext();

        return <<<PROMPT
You are an expert in the K-POP industry.
Research the following K-POP song{$affiliationContext} using Wikipedia, Namuwiki, and official homepage, then collect information.

## Target
- Song Name: {$this->songName}
- Genre: K-POP / Korean Entertainment

## Output Language
{$outputLanguage} (except for alphabet_name which must be in English/alphabet only)

## Required Information
1. alphabet_name: Song title in English alphabet only (e.g., "Dynamite", "Butter", "Next Level"). Use official English title if available, otherwise romanize the title.
2. song_type: One of "title_track", "b_side", "ost", "solo", "collaboration", "pre_release", or null.
3. genres: Array using only "pop", "dance", "ballad", "rnb", "hiphop", "edm", "rock", "jazz", "acoustic".
4. release_date: Release date in YYYY-MM-DD format (e.g., "2020-08-21"). If unknown, set to null.
5. album_name: Album or single name as a string, or null.
6. lyricist: Name of the lyricist(s) who wrote the song lyrics. If multiple writers, separate with commas. If unknown, set to null.
7. composer: Name of the composer(s) who created the music. If multiple composers, separate with commas. If unknown, set to null.
8. arranger: Name of the arranger(s). If multiple arrangers, separate with commas. If unknown, set to null.
9. overview: Background, production, music style, and characteristics of the song, approximately 800 characters.
10. chart_performance: Array of chart performance records (max 10). Format: "Chart Name - Ranking/Certification". Example: "Billboard Hot 100 - #1"
11. sources: Array of referenced pages used for this response. Each item must have:
   - page_title: Specific page title that describes the referenced content.
   - site_name: Website or publication name, not a search/proxy/cache host.
   - url: Direct HTTPS URL of the referenced page.
   - description: Short description of what information was verified from this page.

## Constraints
- Limit your web searches to a maximum of 5 queries
- Prioritize Korean language sources in this order:
  1. Korean Wikipedia: Convert the name to Korean, URL-encode it, and access https://ko.wikipedia.org/wiki/{encoded_korean_name}
  2. Namuwiki: Convert the name to Korean, URL-encode it, and access https://namu.wiki/w/{encoded_korean_name}
  3. Korean official websites
- If direct URL access fails (page not found), use a search query to find the correct page
- If Korean sources are insufficient, then use other language sources (English Wikipedia, etc.)
- Use reliable sources (Wikipedia, Namuwiki, official websites)
- If information is not found, set the field to null
- Do not guess uncertain values. Return null for unknown scalar values and [] for unknown arrays.
- Dates must use YYYY-MM-DD.
- Enum fields must use only the allowed values listed above.
- sources must include only pages actually used for the generated information. Do not include search result URLs, proxy URLs, cache URLs, or vertexaisearch.cloud.google.com URLs.

## Output Format
Return only one valid JSON object. Do not wrap it in Markdown. Use the snake_case field names listed above, including sources.
PROMPT;
    }

    private function buildAffiliationContext(): string
    {
        $parts = [];

        if ($this->agencyName !== null) {
            $parts[] = "with rights held by {$this->agencyName}";
        }

        $performers = [];
        if ($this->groupName !== null) {
            $performers[] = $this->groupName;
        }
        if ($this->talentName !== null) {
            $performers[] = $this->talentName;
        }

        if (count($performers) > 0) {
            $performerList = implode(' and ', $performers);
            $parts[] = "performed by {$performerList}";
        }

        if (count($parts) === 0) {
            return '';
        }

        return ' (' . implode(', ', $parts) . ')';
    }
}
