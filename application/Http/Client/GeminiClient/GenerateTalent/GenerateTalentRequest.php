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
Research the following K-POP idol/talent{$affiliationContext} using Wikipedia, Namuwiki, and official homepage, then collect information.

## Target
- Talent Name: {$this->talentName}
- Genre: K-POP / Korean Entertainment

## Output Language
{$outputLanguage} (except for alphabet_name which must be in English/alphabet only)

## Required Information
1. alphabet_name: Talent name in English alphabet only (e.g., "Jimin", "Lisa", "Karina"). Use official English name if available, otherwise romanize the name.
2. real_name: Real name (birth name) of the talent in the original language (e.g., "박지민", "김제니"). If unknown, set to null.
3. birthday: Birth date in YYYY-MM-DD format (e.g., "1995-10-13"). If unknown, set to null.
4. position: Main group position or role as a string, or null.
5. mbti: One of "INTJ", "INTP", "ENTJ", "ENTP", "INFJ", "INFP", "ENFJ", "ENFP", "ISTJ", "ISFJ", "ESTJ", "ESFJ", "ISTP", "ISFP", "ESTP", "ESFP", or null.
6. zodiac_sign: One of "aries", "taurus", "gemini", "cancer", "leo", "virgo", "libra", "scorpio", "sagittarius", "capricorn", "aquarius", "pisces", or null.
7. english_level: One of "native", "fluent", "conversational", "basic", "none", or null.
8. height: Height in centimeters as a number, or null.
9. blood_type: One of "A", "B", "O", "AB", or null.
10. fandom_name: Personal fandom name as a string, or null.
11. emoji: Single representative emoji string or null.
12. representative_symbol: Representative symbol text or null.
13. overview: Introduction of the person (group affiliation, agency, position, main characteristics) approximately 800 characters.
14. history: Chronological description of upbringing, debut, and career milestones, approximately 1200 characters.
15. appearances: Array of appearances in works (max 10). Format: "Title (Type, Year)". Example: "Hwarang (Drama, 2016)"
16. awards: Array of major individual awards (max 10).
17. sources: Array of referenced pages used for this response. Each item must have:
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
- Extract personal SNS URLs from the external links section of Wikipedia or Namuwiki pages
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
