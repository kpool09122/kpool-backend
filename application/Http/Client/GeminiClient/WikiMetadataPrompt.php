<?php

declare(strict_types=1);

namespace Application\Http\Client\GeminiClient;

final readonly class WikiMetadataPrompt
{
    public function __construct(
        private int $titleLength,
        private int $descriptionLength,
        private int $keywordCount,
        private int $keywordLength,
    ) {
    }

    public function instructions(): string
    {
        return <<<PROMPT
## SEO Metadata
Include these additional top-level fields in the JSON response, in the requested output language:
- title: A concise, factual SEO title including the subject's name, at most {$this->titleLength} characters.
- meta_description: A factual summary of this Wiki for search results, at most {$this->descriptionLength} characters.
- keywords: At most {$this->keywordCount} relevant, distinct strings, each at most {$this->keywordLength} characters.
Use only information supported by the research. If unavailable, return null for title and meta_description and [] for keywords.
PROMPT;
    }
}
