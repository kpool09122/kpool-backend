<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Block;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Block\BlockType;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TableBlock;

class TableBlockTest extends TestCase
{
    /**
     * 正常系: ヘッダー付きでインスタンスが生成されること
     */
    public function test__constructWithHeaders(): void
    {
        $displayOrder = 1;
        $rows = [
            ['2018-06-13', 'デビュー', 'ミニアルバム発売'],
            ['2020-02-21', '1stアルバム', 'フルアルバム発売'],
        ];
        $headers = ['日付', 'タイトル', '説明'];

        $block = new TableBlock(
            displayOrder: $displayOrder,
            rows: $rows,
            headers: $headers,
        );

        $this->assertSame($displayOrder, $block->displayOrder());
        $this->assertSame(BlockType::TABLE, $block->blockType());
        $this->assertSame($rows, $block->rows());
        $this->assertSame($headers, $block->headers());
    }

    /**
     * 正常系: ヘッダーなしでインスタンスが生成されること
     */
    public function test__constructWithoutHeaders(): void
    {
        $block = new TableBlock(
            displayOrder: 0,
            rows: [['セル1', 'セル2']],
        );

        $this->assertNull($block->headers());
    }

    /**
     * 正常系: 空のrows配列でインスタンスが生成されること
     */
    public function test__constructWithEmptyRows(): void
    {
        $block = new TableBlock(
            displayOrder: 0,
            rows: [],
        );

        $this->assertEmpty($block->rows());
    }
}
