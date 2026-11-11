<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Block;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\Block\BlockType;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ImageBlock;
use Tests\Helper\StrTestHelper;

class ImageBlockTest extends TestCase
{
    /**
     * 正常系: 全ての値を指定してインスタンスが生成されること
     */
    public function test__construct(): void
    {
        $displayOrder = 1;
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $caption = 'テスト画像';
        $alt = '代替テキスト';

        $block = new ImageBlock(
            displayOrder: $displayOrder,
            imageIdentifier: $imageIdentifier,
            caption: $caption,
            imageWidth: 450,
            alt: $alt,
        );

        $this->assertSame($displayOrder, $block->displayOrder());
        $this->assertSame(BlockType::IMAGE, $block->blockType());
        $this->assertSame($imageIdentifier, $block->imageIdentifier());
        $this->assertSame($caption, $block->caption());
        $this->assertSame(450, $block->imageWidth());
        $this->assertSame($alt, $block->alt());
    }

    /**
     * 正常系: captionとaltがnullでインスタンスが生成されること
     */
    public function test__constructWithNullOptionalValues(): void
    {
        $block = new ImageBlock(
            displayOrder: 0,
            imageIdentifier: new ImageIdentifier(StrTestHelper::generateUuid()),
        );

        $this->assertNull($block->caption());
        $this->assertNull($block->imageWidth());
        $this->assertNull($block->alt());
    }

    public function testRejectsNonPositiveImageWidth(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ImageBlock(
            displayOrder: 1,
            imageIdentifier: new ImageIdentifier(StrTestHelper::generateUuid()),
            imageWidth: -1,
        );
    }
}
