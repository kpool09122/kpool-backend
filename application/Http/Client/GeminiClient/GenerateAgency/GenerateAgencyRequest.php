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
                        'ceo_name' => ['type' => 'string', 'nullable' => true],
                        'founded_year' => ['type' => 'integer', 'nullable' => true],
                        'description' => ['type' => 'string', 'nullable' => true],
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
2. CEO name (representative director, CEO, etc.)
3. Founded year (year only, e.g., 2005)
4. Detailed description of the agency (history, major artists, characteristics, etc. approximately 2000 characters)
5. Official SNS URLs (extract from external links section of Wikipedia/Namuwiki):
   - instagram_url: Official Instagram URL (e.g., "https://www.instagram.com/jaboritory/")
   - tiktok_url: Official TikTok URL (e.g., "https://www.tiktok.com/@jypentertainment")
   - youtube_url: Official YouTube channel URL (e.g., "https://www.youtube.com/@JYPEntertainment")
   - x_url: Official X(Twitter) URL (e.g., "https://x.com/jypentertainment" or "https://twitter.com/jypentertainment")

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
