<?php

declare(strict_types=1);

namespace Application\Http\Client\GeminiClient\GenerateGroup;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

final readonly class GenerateGroupRequest
{
    public function __construct(
        private string $groupName,
        private string $language,
        private ?string $agencyName = null,
    ) {
    }

    public function groupName(): string
    {
        return $this->groupName;
    }

    public function language(): string
    {
        return $this->language;
    }

    public function agencyName(): ?string
    {
        return $this->agencyName;
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
                    'google_search' => [
                        'dynamic_retrieval_config' => [
                            'dynamic_threshold' => 0.5,
                        ],
                    ],
                ],
            ],
            'contents' => [
                [
                    'parts' => [
                        ['text' => $this->buildPrompt()],
                    ],
                ],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'alphabet_name' => ['type' => 'string', 'nullable' => true],
                        'overview' => ['type' => 'string', 'nullable' => true],
                        'history' => ['type' => 'string', 'nullable' => true],
                        'representative_songs' => [
                            'type' => 'array', 'items' => ['type' => 'string'], 'nullable' => true,
                        ],
                        'awards' => [
                            'type' => 'array', 'items' => ['type' => 'string'], 'nullable' => true,
                        ],
                        'members' => [
                            'type' => 'array', 'items' => ['type' => 'string'], 'nullable' => true,
                        ],
                        'fandom_name' => ['type' => 'string', 'nullable' => true],
                        'instagram_url' => ['type' => 'string', 'nullable' => true],
                        'tiktok_url' => ['type' => 'string', 'nullable' => true],
                        'youtube_url' => ['type' => 'string', 'nullable' => true],
                        'x_url' => ['type' => 'string', 'nullable' => true],
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

        $agencyContext = $this->agencyName !== null
            ? " affiliated with {$this->agencyName}"
            : '';

        return <<<PROMPT
You are an expert in the K-POP industry.
Research the following K-POP group/artist{$agencyContext} using Wikipedia, Namuwiki, and official homepage, then collect information.

## Target
- Group/Artist Name: {$this->groupName}
- Genre: K-POP / Korean Entertainment

## Output Language
{$outputLanguage} (except for alphabet_name which must be in English/alphabet only)

## Required Information
1. alphabet_name: Group/Artist name in English alphabet only (e.g., "BTS", "BLACKPINK", "NewJeans"). Use official English name if available, otherwise romanize the name.
2. overview: Overview of the group (concept, characteristics, agency, etc.) approximately 800 characters.
3. history: Chronological description of formation, debut, and career milestones, approximately 1200 characters.
4. representative_songs: Array of representative songs (max 10). Format: "Song Title (Year)". Example: "Dynamite (2020)"
5. awards: Array of major awards (max 10). Include award name and year.
6. members: Array of current member names (stage names).
7. fandom_name: Official name for the fan community (e.g., "ARMY" for BTS, "BLINK" for BLACKPINK, "Bunnies" for NewJeans). If unknown, set to null.
8. Official SNS URLs (extract from external links section of Wikipedia/Namuwiki):
   - instagram_url: Official Instagram URL (e.g., "https://www.instagram.com/newjeans_official/")
   - tiktok_url: Official TikTok URL (e.g., "https://www.tiktok.com/@newjeans_official")
   - youtube_url: Official YouTube channel URL (e.g., "https://www.youtube.com/@NewJeans_official")
   - x_url: Official X(Twitter) URL (e.g., "https://x.com/NewJeans_ADOR" or "https://twitter.com/NewJeans_ADOR")

## Constraints
- Limit your web searches to a maximum of 5 queries
- Prioritize Korean language sources in this order:
  1. Korean Wikipedia: Convert the name to Korean, URL-encode it, and access https://ko.wikipedia.org/wiki/{encoded_korean_name}
  2. Namuwiki: Convert the name to Korean, URL-encode it, and access https://namu.wiki/w/{encoded_korean_name}
  3. Korean official websites
- If direct URL access fails (page not found), use a search query to find the correct page
- Extract official SNS URLs from the external links section of Wikipedia or Namuwiki pages
- If Korean sources are insufficient, then use other language sources (English Wikipedia, etc.)
- Use reliable sources (Wikipedia, Namuwiki, official websites)
- If information is not found, set the field to null
PROMPT;
    }
}
