<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Block;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Block\BlockType;
use Source\Wiki\Wiki\Domain\ValueObject\Block\QuoteBlock;

class QuoteBlockTest extends TestCase
{
    /**
     * 正常系: 全ての値を指定してインスタンスが生成されること
     */
    public function test__construct(): void
    {
        $displayOrder = 1;
        $content = '努力は裏切らない';
        $source = 'メンバー名';

        $block = new QuoteBlock(
            displayOrder: $displayOrder,
            content: $content,
            source: $source,
        );

        $this->assertSame($displayOrder, $block->displayOrder());
        $this->assertSame(BlockType::QUOTE, $block->blockType());
        $this->assertSame($content, $block->content());
        $this->assertSame($source, $block->source());
    }

    /**
     * 正常系: sourceがnullでインスタンスが生成されること
     */
    public function test__constructWithNullSource(): void
    {
        $block = new QuoteBlock(
            displayOrder: 0,
            content: '名言',
        );

        $this->assertNull($block->source());
    }
}
