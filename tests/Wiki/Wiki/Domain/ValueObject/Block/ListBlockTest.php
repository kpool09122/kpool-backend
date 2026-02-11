<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Block;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Block\BlockType;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ListBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ListType;

class ListBlockTest extends TestCase
{
    /**
     * 正常系: 全ての値を指定してインスタンスが生成されること
     */
    public function test__construct(): void
    {
        $displayOrder = 1;
        $listType = ListType::BULLET;
        $items = ['項目1', '項目2', '項目3'];

        $block = new ListBlock(
            displayOrder: $displayOrder,
            listType: $listType,
            items: $items,
        );

        $this->assertSame($displayOrder, $block->displayOrder());
        $this->assertSame(BlockType::LIST, $block->blockType());
        $this->assertSame($listType, $block->listType());
        $this->assertSame($items, $block->items());
    }

    /**
     * 正常系: numberedタイプでインスタンスが生成されること
     */
    public function test__constructWithNumberedType(): void
    {
        $listType = ListType::NUMBERED;

        $block = new ListBlock(
            displayOrder: 0,
            listType: $listType,
            items: ['手順1', '手順2'],
        );

        $this->assertSame($listType, $block->listType());
    }

    /**
     * 正常系: 空のitems配列でインスタンスが生成されること
     */
    public function test__constructWithEmptyItems(): void
    {
        $block = new ListBlock(
            displayOrder: 0,
            listType: ListType::BULLET,
            items: [],
        );

        $this->assertEmpty($block->items());
    }
}
