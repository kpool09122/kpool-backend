<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject;

use InvalidArgumentException;
use Source\Wiki\Wiki\Domain\ValueObject\SeoKeywords;
use Tests\TestCase;

class SeoKeywordsTest extends TestCase
{
    public function testCreatesSeoKeywords(): void
    {
        $keywords = new SeoKeywords(['TWICE', 'K-pop', str_repeat('あ', SeoKeywords::MAX_KEYWORD_LENGTH)]);

        $this->assertSame(['TWICE', 'K-pop', str_repeat('あ', SeoKeywords::MAX_KEYWORD_LENGTH)], $keywords->values());
    }

    public function testAllowsEmptyKeywordList(): void
    {
        $keywords = new SeoKeywords([]);

        $this->assertSame([], $keywords->values());
    }

    public function testThrowsInvalidArgumentExceptionWhenTooMany(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SeoKeywords(['a', 'b', 'c', 'd', 'e', 'f']);
    }

    public function testThrowsInvalidArgumentExceptionWhenKeywordIsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SeoKeywords(['']);
    }

    public function testThrowsInvalidArgumentExceptionWhenKeywordIsTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SeoKeywords([str_repeat('あ', SeoKeywords::MAX_KEYWORD_LENGTH + 1)]);
    }
}
