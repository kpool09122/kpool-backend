<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Block;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Block\BlockType;
use Source\Wiki\Wiki\Domain\ValueObject\Block\GoogleTrendsBlock;

class GoogleTrendsBlockTest extends TestCase
{
    public function testWithKeyPreservesTypeAndOrder(): void
    {
        $block = new GoogleTrendsBlock(7, 'original');
        $keyed = $block->withKey('updated');
        self::assertSame(BlockType::GOOGLE_TRENDS, $keyed->blockType());
        self::assertSame(7, $keyed->displayOrder());
        self::assertSame('updated', $keyed->key());
        self::assertSame('original', $block->key());
    }
}
