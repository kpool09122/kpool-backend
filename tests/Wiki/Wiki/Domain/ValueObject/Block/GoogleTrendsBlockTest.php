<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Block;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Wiki\Domain\ValueObject\Block\BlockType;
use Source\Wiki\Wiki\Domain\ValueObject\Block\GoogleTrendsBlock;

class GoogleTrendsBlockTest extends TestCase
{
    public function testPreservesConfigurationAndOnlyReplacesKey(): void
    {
        $block = new GoogleTrendsBlock(7, '少女時代', 'KR', 'today 5-y', 'original');
        $keyed = $block->withKey('new');
        self::assertSame(BlockType::GOOGLE_TRENDS, $keyed->blockType());
        self::assertSame(7, $keyed->displayOrder());
        self::assertSame('少女時代', $keyed->searchTerm());
        self::assertSame('KR', $keyed->region());
        self::assertSame('today 5-y', $keyed->timeRange());
        self::assertSame('new', $keyed->key());
        self::assertSame('original', $block->key());
    }

    #[TestWith(['', 'JP', 'today 12-m'])]
    #[TestWith(['   ', 'JP', 'today 12-m'])]
    #[TestWith(['test', 'US', 'today 12-m'])]
    #[TestWith(['test', 'jp', 'today 12-m'])]
    #[TestWith(['test', 'JP', 'today 10-y'])]
    public function testRejectsInvalidConfiguration(string $searchTerm, string $region, string $timeRange): void
    {
        $this->expectException(InvalidArgumentException::class);
        new GoogleTrendsBlock(0, $searchTerm, $region, $timeRange);
    }

    public function testSearchTermLengthBoundary(): void
    {
        self::assertSame(str_repeat('あ', 100), (new GoogleTrendsBlock(0, str_repeat('あ', 100)))->searchTerm());
        $this->expectException(InvalidArgumentException::class);
        new GoogleTrendsBlock(0, str_repeat('あ', 101));
    }

    #[TestWith([Language::JAPANESE, 'JP'])]
    #[TestWith([Language::KOREAN, 'KR'])]
    #[TestWith([Language::ENGLISH, ''])]
    public function testDefaultsForWikiLanguage(Language $language, string $region): void
    {
        $block = new GoogleTrendsBlock(0, 'Title', GoogleTrendsBlock::defaultRegion($language));
        self::assertSame($region, $block->region());
        self::assertSame('today 12-m', $block->timeRange());
        self::assertNull($block->key());
    }
}
