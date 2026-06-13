<?php

declare(strict_types=1);

namespace Application\Http\Client\GeminiClient\GenerateAgency;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

final readonly class GenerateAgencyRequest
{
    public function __construct(
        private string $agencyName,
        private string $language,
    ) {
    }

    public function agencyName(): string
    {
        return $this->agencyName;
    }

    public function language(): string
    {
        return $this->language;
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

        return <<<PROMPT
You are an expert in the K-POP industry.
Research the following agency using Wikipedia, Namuwiki, and official homepage, then collect information.

## Target
- Agency Name: {$this->agencyName}
- Genre: K-POP / Korean Entertainment

## Output Language
{$outputLanguage} (except for alphabet_name which must be in English/alphabet only)

## Required Information
1. alphabet_name: Agency name in English alphabet only (e.g., "JYP Entertainment", "SM Entertainment"). Use official English name if available, otherwise romanize the name.
2. ceo: CEO name (representative director, CEO, etc.) or null.
3. founded_in: Founding date in YYYY-MM-DD format or null. If only the year is known, return YYYY-01-01.
4. status: One of "active", "closed", "merged", "rebranded", or null.
5. official_website: Official website URL or null.
4. overview: Company overview (characteristics, business areas, market position) approximately 800 characters.
5. history: Chronological description of founding, growth, and major events, approximately 1200 characters.
6. artists: Array of currently affiliated artist/group names (max 20).
7. social_links: Array of official HTTPS SNS URLs. Include Instagram, TikTok, YouTube, and X(Twitter) URLs when found.
8. sources: Array of referenced pages used for this response. Each item must have:
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
- Extract official SNS URLs from the external links section of Wikipedia or Namuwiki pages
- If Korean sources are insufficient, then use other language sources (English Wikipedia, etc.)
- Use reliable sources (Wikipedia, Namuwiki, official websites)
- If information is not found, set the field to null
- Do not guess uncertain values. Return null for unknown scalar values and [] for unknown arrays.
- Dates must use YYYY-MM-DD.
- Enum fields must use only the allowed lowercase values listed above.
- sources must include only pages actually used for the generated information. Do not include search result URLs, proxy URLs, cache URLs, or vertexaisearch.cloud.google.com URLs.

## Output Format
Return only one valid JSON object. Do not wrap it in Markdown. Use the snake_case field names listed above, including sources.
PROMPT;
    }
}
