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
2. group_type: One of "boy_group", "girl_group", "co_ed", or null.
3. status: One of "active", "disbanded", "hiatus", or null.
4. generation: One of "1st", "2nd", "3rd", "4th", "5th", or null.
5. debut_date: Debut date in YYYY-MM-DD format or null.
6. disband_date: Disbandment date in YYYY-MM-DD format or null.
7. fandom_name: Official name for the fan community (e.g., "ARMY" for BTS, "BLINK" for BLACKPINK, "Bunnies" for NewJeans). If unknown, set to null.
8. official_colors: Array of official colors as HEX color codes (e.g., "#FF5733"). If unknown, return [].
9. emoji: Single representative emoji string or null.
10. representative_symbol: Representative symbol text or null.
11. overview: Overview of the group (concept, characteristics, agency, etc.) approximately 800 characters.
12. history: Chronological description of formation, debut, and career milestones, approximately 1200 characters.
13. representative_songs: Array of representative songs (max 10). Format: "Song Title (Year)". Example: "Dynamite (2020)"
14. awards: Array of major awards (max 10). Include award name and year.
15. members: Array of current member names (stage names).

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
- Do not guess uncertain values. Return null for unknown scalar values and [] for unknown arrays.
- Dates must use YYYY-MM-DD.
- Enum fields must use only the allowed values listed above.

## Output Format
Return only one valid JSON object. Do not wrap it in Markdown. Use the snake_case field names listed above.
PROMPT;
    }
}
