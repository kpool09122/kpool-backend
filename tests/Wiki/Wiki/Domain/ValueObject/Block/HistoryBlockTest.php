<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Block;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Block\BlockType;
use Source\Wiki\Wiki\Domain\ValueObject\Block\HistoryBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\HistoryEntry;

class HistoryBlockTest extends TestCase
{
    public function testPreservesEntryOrderForEditing(): void
    {
        $entries = [
            new HistoryEntry(2024, 'Debut'),
            new HistoryEntry(2023, 'Announcement'),
            new HistoryEntry(2024, 'First concert'),
        ];
        $block = new HistoryBlock(3, $entries);

        self::assertSame(BlockType::HISTORY, $block->blockType());
        self::assertSame(3, $block->displayOrder());
        self::assertSame($entries, $block->entries());
    }
}
