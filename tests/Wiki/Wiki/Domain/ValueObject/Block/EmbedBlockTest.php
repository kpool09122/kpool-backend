<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Block;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Block\BlockType;
use Source\Wiki\Wiki\Domain\ValueObject\Block\EmbedBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\EmbedProvider;

class EmbedBlockTest extends TestCase
{
    /**
     * 正常系: 全ての値を指定してインスタンスが生成されること
     */
    public function test__construct(): void
    {
        $displayOrder = 1;
        $provider = EmbedProvider::YOUTUBE;
        $embedId = 'dQw4w9WgXcQ';
        $caption = 'テスト動画';

        $block = new EmbedBlock(
            displayOrder: $displayOrder,
            provider: $provider,
            embedId: $embedId,
            caption: $caption,
        );

        $this->assertSame($displayOrder, $block->displayOrder());
        $this->assertSame(BlockType::EMBED, $block->blockType());
        $this->assertSame($provider, $block->provider());
        $this->assertSame($embedId, $block->embedId());
        $this->assertSame($caption, $block->caption());
    }

    /**
     * 正常系: captionがnullでインスタンスが生成されること
     */
    public function test__constructWithNullCaption(): void
    {
        $block = new EmbedBlock(
            displayOrder: 0,
            provider: EmbedProvider::SPOTIFY,
            embedId: '4iV5W9uYEdYUVa79Axb7Rh',
        );

        $this->assertNull($block->caption());
    }

    /**
     * 正常系: 各プロバイダーでインスタンスが生成されること
     */
    public function testWithEachProvider(): void
    {
        foreach (EmbedProvider::cases() as $provider) {
            $block = new EmbedBlock(
                displayOrder: 0,
                provider: $provider,
                embedId: 'test-id',
            );

            $this->assertSame($provider, $block->provider());
        }
    }
}
