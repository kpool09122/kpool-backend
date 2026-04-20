<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Block;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TableCell;

class TableCellTest extends TestCase
{
    public function testFromArrayAndToArray(): void
    {
        $cell = TableCell::fromArray([
            'content' => 'タイトル',
            'colspan' => 2,
        ]);

        $this->assertSame('タイトル', $cell->content());
        $this->assertSame(2, $cell->colspan());
        $this->assertSame(
            ['content' => 'タイトル', 'colspan' => 2],
            $cell->toArray()
        );
    }

    public function testWithContentKeepsColspan(): void
    {
        $cell = new TableCell('原文', 3);

        $translated = $cell->withContent('翻訳後');

        $this->assertSame('翻訳後', $translated->content());
        $this->assertSame(3, $translated->colspan());
    }
}
