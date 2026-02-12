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
                        'lyricist' => ['type' => 'string', 'nullable' => true],
                        'composer' => ['type' => 'string', 'nullable' => true],
                        'release_date' => ['type' => 'string', 'nullable' => true],
                        'overview' => ['type' => 'string', 'nullable' => true],
                        'chart_performance' => [
                            'type' => 'array', 'items' => ['type' => 'string'], 'nullable' => true,
                        ],
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
2. lyricist: Name of the lyricist(s) who wrote the song lyrics. If multiple writers, separate with commas. If unknown, set to null.
3. composer: Name of the composer(s) who created the music. If multiple composers, separate with commas. If unknown, set to null.
4. release_date: Release date in ISO 8601 format (YYYY-MM-DD, e.g., "2020-08-21"). If unknown, set to null.
5. overview: Background, production, music style, and characteristics of the song, approximately 800 characters.
6. chart_performance: Array of chart performance records (max 10). Format: "Chart Name - Ranking/Certification". Example: "Billboard Hot 100 - #1"

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
