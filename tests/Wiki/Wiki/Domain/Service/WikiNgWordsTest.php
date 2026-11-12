<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\Service;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\Service\WikiNgWords;

class WikiNgWordsTest extends TestCase
{
    public function testDictionaryContainsUniqueNormalizedEntries(): void
    {
        $terms = [...WikiNgWords::WHOLE_WORDS_AND_PHRASES, ...WikiNgWords::TEXT_EXPRESSIONS];
        $normalizedTerms = array_map(
            static fn (string $term): string => mb_strtolower(trim(mb_convert_kana($term, 'asKV', 'UTF-8')), 'UTF-8'),
            $terms,
        );

        $this->assertNotContains('', $normalizedTerms);
        $this->assertSame($normalizedTerms, array_values(array_unique($normalizedTerms)));
    }
}
