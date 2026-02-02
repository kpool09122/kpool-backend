<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Block;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Block\BlockType;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TextBlock;

class TextBlockTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     */
    public function test__construct(): void
    {
        $displayOrder = 1;
        $content = 'テスト本文テキスト';

        $block = new TextBlock(
            displayOrder: $displayOrder,
            content: $content,
        );

        $this->assertSame($displayOrder, $block->displayOrder());
        $this->assertSame(BlockType::TEXT, $block->blockType());
        $this->assertSame($content, $block->content());
    }
}
