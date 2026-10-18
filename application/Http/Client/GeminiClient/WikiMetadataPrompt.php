<?php

declare(strict_types=1);

namespace Application\Http\Client\GeminiClient;

use Source\Wiki\Wiki\Domain\ValueObject\MetaDescription;
use Source\Wiki\Wiki\Domain\ValueObject\SeoKeywords;
use Source\Wiki\Wiki\Domain\ValueObject\SeoTitle;

final class WikiMetadataPrompt
{
    public static function instructions(): string
    {
        $titleLength = SeoTitle::MAX_LENGTH;
        $descriptionLength = MetaDescription::MAX_LENGTH;
        $keywordCount = SeoKeywords::MAX_COUNT;
        $keywordLength = SeoKeywords::MAX_KEYWORD_LENGTH;

        return <<<PROMPT
## SEO Metadata
Include these additional top-level fields in the JSON response, in the requested output language:
- title: A concise, factual SEO title including the subject's name, at most {$titleLength} characters.
- meta_description: A factual summary of this Wiki for search results, at most {$descriptionLength} characters.
- keywords: At most {$keywordCount} relevant, distinct strings, each at most {$keywordLength} characters.
Use only information supported by the research. If unavailable, return null for title and meta_description and [] for keywords.
PROMPT;
    }
}
