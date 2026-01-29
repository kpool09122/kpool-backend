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
                        'description' => ['type' => 'string', 'nullable' => true],
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
Research the following K-POP group/artist using Wikipedia and official homepage, then collect information.

## Target
- Group/Artist Name: {$this->groupName}
- Genre: K-POP / Korean Entertainment

## Output Language
{$outputLanguage} (except for alphabet_name which must be in English/alphabet only)

## Required Information
1. alphabet_name: Group/Artist name in English alphabet only (e.g., "BTS", "BLACKPINK", "NewJeans"). Use official English name if available, otherwise romanize the name.
2. Detailed description of the group/artist (debut date, members, major achievements, characteristics, etc. approximately 2000 characters)

## Constraints
- Limit your web searches to a maximum of 5 queries
- Prioritize Korean language sources: search Korean Wikipedia (ko.wikipedia.org) and Korean official websites first
- If Korean sources are insufficient, then use other language sources (English Wikipedia, etc.)
- Use reliable sources (Wikipedia, official websites)
- If information is not found, set the field to null
PROMPT;
    }
}
