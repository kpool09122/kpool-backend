<?php

declare(strict_types=1);

namespace Application\Http\Client\GeminiClient\GenerateTalent;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

final readonly class GenerateTalentRequest
{
    /**
     * @param string[] $groupNames
     */
    public function __construct(
        private string $talentName,
        private string $language,
        private ?string $agencyName = null,
        private array $groupNames = [],
    ) {
    }

    public function talentName(): string
    {
        return $this->talentName;
    }

    public function language(): string
    {
        return $this->language;
    }

    public function agencyName(): ?string
    {
        return $this->agencyName;
    }

    /**
     * @return string[]
     */
    public function groupNames(): array
    {
        return $this->groupNames;
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
                        'real_name' => ['type' => 'string', 'nullable' => true],
                        'birthday' => ['type' => 'string', 'nullable' => true],
                        'description' => ['type' => 'string', 'nullable' => true],
                        'english_level' => ['type' => 'string', 'nullable' => true],
                        'english_background' => ['type' => 'string', 'nullable' => true],
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

        $affiliationContext = $this->buildAffiliationContext();

        return <<<PROMPT
You are an expert in the K-POP industry.
Research the following K-POP idol/talent{$affiliationContext} using Wikipedia, Namuwiki, and official homepage, then collect information.

## Target
- Talent Name: {$this->talentName}
- Genre: K-POP / Korean Entertainment

## Output Language
{$outputLanguage} (except for alphabet_name which must be in English/alphabet only)

## Required Information
1. alphabet_name: Talent name in English alphabet only (e.g., "Jimin", "Lisa", "Karina"). Use official English name if available, otherwise romanize the name.
2. real_name: Real name (birth name) of the talent in the original language (e.g., "박지민", "김제니"). If unknown, set to null.
3. birthday: Birth date in ISO 8601 format (YYYY-MM-DD, e.g., "1995-10-13"). If unknown, set to null.
4. Detailed description of the talent (group affiliation, debut date, position in group, major achievements, characteristics, etc. approximately 2000 characters)
5. English proficiency:
   - english_level: Level of English proficiency (e.g., "Native", "Fluent", "Conversational", "Basic", "None"). If unknown, set to null.
   - english_background: Background of English ability (e.g., "Born and raised in Australia", "Studied abroad in the US for 3 years", "Self-taught"). If unknown, set to null.
6. Personal SNS URLs (extract from external links section of Wikipedia/Namuwiki):
   - instagram_url: Personal Instagram URL (e.g., "https://www.instagram.com/j.m/")
   - tiktok_url: Personal TikTok URL (e.g., "https://www.tiktok.com/@j.m")
   - youtube_url: Personal YouTube channel URL (e.g., "https://www.youtube.com/@jimin")
   - x_url: Personal X(Twitter) URL (e.g., "https://x.com/jikiE_twt" or "https://twitter.com/jikiE_twt")

## Constraints
- Limit your web searches to a maximum of 5 queries
- Prioritize Korean language sources in this order:
  1. Korean Wikipedia: Convert the name to Korean, URL-encode it, and access https://ko.wikipedia.org/wiki/{encoded_korean_name}
  2. Namuwiki: Convert the name to Korean, URL-encode it, and access https://namu.wiki/w/{encoded_korean_name}
  3. Korean official websites
- If direct URL access fails (page not found), use a search query to find the correct page
- Extract personal SNS URLs from the external links section of Wikipedia or Namuwiki pages
- If Korean sources are insufficient, then use other language sources (English Wikipedia, etc.)
- Use reliable sources (Wikipedia, Namuwiki, official websites)
- If information is not found, set the field to null
PROMPT;
    }

    private function buildAffiliationContext(): string
    {
        $parts = [];

        if ($this->agencyName !== null) {
            $parts[] = "contracted with {$this->agencyName}";
        }

        if (count($this->groupNames) > 0) {
            $groupList = implode(', ', $this->groupNames);
            $parts[] = "member of {$groupList}";
        }

        if (count($parts) === 0) {
            return '';
        }

        return ' (' . implode(' and ', $parts) . ')';
    }
}
