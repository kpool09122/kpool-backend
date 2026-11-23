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
    #[TestWith(['today 3-m'])]
    #[TestWith(['today 6-m'])]
    #[TestWith(['today 12-m'])]
    #[TestWith(['today 2-y'])]
    #[TestWith(['today 5-y'])]
    #[TestWith(['today 10-y'])]
    public function testAcceptsSupportedTimeRanges(string $timeRange): void
    {
        $block = new GoogleTrendsBlock(0, 'TWICE', 'JP', $timeRange);
        self::assertSame($timeRange, $block->timeRange());
    }

    #[TestWith(['Karina', 'aespa', 'Karina aespa'])]
    #[TestWith(['Nayeon', null, 'Nayeon'])]
    public function testDefaultSearchTerm(string $name, ?string $groupName, string $expected): void
    {
        self::assertSame($expected, GoogleTrendsBlock::defaultSearchTerm($name, $groupName));
    }

    public function testDefaultSearchTermFitsBlockLimit(): void
    {
        $searchTerm = GoogleTrendsBlock::defaultSearchTerm(str_repeat('あ', 64), str_repeat('い', 64));
        self::assertSame(100, mb_strlen($searchTerm));
        self::assertSame($searchTerm, (new GoogleTrendsBlock(0, $searchTerm))->searchTerm());
    }

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
    #[TestWith(['test', '', 'today 12-m'])]
    #[TestWith(['test', 'US', 'today 12-m'])]
    #[TestWith(['test', 'jp', 'today 12-m'])]
    #[TestWith(['test', 'JP', 'today 20-y'])]
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
    #[TestWith([Language::ENGLISH, 'all'])]
    public function testDefaultsForWikiLanguage(Language $language, string $region): void
    {
        $block = new GoogleTrendsBlock(0, 'Title', GoogleTrendsBlock::defaultRegion($language));
        self::assertSame($region, $block->region());
        self::assertSame('today 10-y', $block->timeRange());
        self::assertNull($block->key());
    }
}
