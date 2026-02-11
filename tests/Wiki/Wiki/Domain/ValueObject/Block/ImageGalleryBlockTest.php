<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Block;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\Block\BlockType;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ImageGalleryBlock;
use Tests\Helper\StrTestHelper;

class ImageGalleryBlockTest extends TestCase
{
    /**
     * 正常系: 全ての値を指定してインスタンスが生成されること
     */
    public function test__construct(): void
    {
        $displayOrder = 1;
        $imageIdentifiers = [
            new ImageIdentifier(StrTestHelper::generateUuid()),
            new ImageIdentifier(StrTestHelper::generateUuid()),
            new ImageIdentifier(StrTestHelper::generateUuid()),
        ];
        $caption = 'テストギャラリー';

        $block = new ImageGalleryBlock(
            displayOrder: $displayOrder,
            imageIdentifiers: $imageIdentifiers,
            caption: $caption,
        );

        $this->assertSame($displayOrder, $block->displayOrder());
        $this->assertSame(BlockType::IMAGE_GALLERY, $block->blockType());
        $this->assertSame($imageIdentifiers, $block->imageIdentifiers());
        $this->assertSame($caption, $block->caption());
    }

    /**
     * 正常系: captionがnullでインスタンスが生成されること
     */
    public function test__constructWithNullCaption(): void
    {
        $block = new ImageGalleryBlock(
            displayOrder: 0,
            imageIdentifiers: [
                new ImageIdentifier(StrTestHelper::generateUuid()),
            ],
        );

        $this->assertNull($block->caption());
    }

    /**
     * 正常系: 空の画像配列でインスタンスが生成されること
     */
    public function test__constructWithEmptyImageIdentifiers(): void
    {
        $block = new ImageGalleryBlock(
            displayOrder: 0,
            imageIdentifiers: [],
        );

        $this->assertEmpty($block->imageIdentifiers());
    }
}
