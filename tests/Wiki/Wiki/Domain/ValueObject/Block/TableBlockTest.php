<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Block;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Block\BlockType;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TableBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TableCell;

class TableBlockTest extends TestCase
{
    /**
     * 正常系: ヘッダー付きでインスタンスが生成されること
     */
    public function test__constructWithHeaders(): void
    {
        $displayOrder = 1;
        $rowCells = [
            [
                new TableCell('2018-06-13'),
                new TableCell('デビュー'),
                new TableCell('ミニアルバム発売'),
            ],
            [
                new TableCell('2020-02-21'),
                new TableCell('1stアルバム', 2),
            ],
        ];
        $headerCells = [
            new TableCell('日付'),
            new TableCell('タイトル'),
            new TableCell('説明'),
        ];
        $tableWidth = '80%';

        $block = new TableBlock(
            displayOrder: $displayOrder,
            rowCells: $rowCells,
            headerCells: $headerCells,
            tableWidth: $tableWidth,
        );

        $this->assertSame($displayOrder, $block->displayOrder());
        $this->assertSame(BlockType::TABLE, $block->blockType());
        $this->assertSame($rowCells, $block->rowCells());
        $this->assertSame($headerCells, $block->headerCells());
        $this->assertSame($tableWidth, $block->tableWidth());
    }

    /**
     * 正常系: ヘッダーなしでインスタンスが生成されること
     */
    public function test__constructWithoutHeaders(): void
    {
        $block = new TableBlock(
            displayOrder: 0,
            rowCells: [[new TableCell('セル1'), new TableCell('セル2')]],
        );

        $this->assertNull($block->headerCells());
        $this->assertNull($block->tableWidth());
    }

    /**
     * 正常系: 空のrows配列でインスタンスが生成されること
     */
    public function test__constructWithEmptyRows(): void
    {
        $block = new TableBlock(
            displayOrder: 0,
            rowCells: [],
        );

        $this->assertEmpty($block->rowCells());
    }
}
