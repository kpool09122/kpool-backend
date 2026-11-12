<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Block;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\Block\BlockType;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ProfileCardListBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ProfileCardShape;
use Tests\Helper\StrTestHelper;

class ProfileCardListBlockTest extends TestCase
{
    /**
     * 正常系: 全ての値を指定してインスタンスが生成されること
     */
    public function test__construct(): void
    {
        $displayOrder = 1;
        $wikiIdentifiers = [
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new WikiIdentifier(StrTestHelper::generateUuid()),
        ];
        $title = 'メンバー一覧';

        $block = new ProfileCardListBlock(
            displayOrder: $displayOrder,
            wikiIdentifiers: $wikiIdentifiers,
            cardShape: ProfileCardShape::PORTRAIT,
            title: $title,
        );

        $this->assertSame($displayOrder, $block->displayOrder());
        $this->assertSame(BlockType::PROFILE_CARD_LIST, $block->blockType());
        $this->assertSame($wikiIdentifiers, $block->wikiIdentifiers());
        $this->assertSame($title, $block->title());
    }

    /**
     * 正常系: titleがnullでインスタンスが生成されること
     */
    public function test__constructWithNullTitle(): void
    {
        $block = new ProfileCardListBlock(
            displayOrder: 0,
            wikiIdentifiers: [
                new WikiIdentifier(StrTestHelper::generateUuid()),
            ],
            cardShape: ProfileCardShape::PORTRAIT,
        );

        $this->assertNull($block->title());
    }

    /**
     * 正常系: 空のwikiIdentifiers配列でインスタンスが生成されること
     */
    public function test__constructWithEmptyWikiIdentifiers(): void
    {
        $block = new ProfileCardListBlock(
            displayOrder: 0,
            wikiIdentifiers: [],
            cardShape: ProfileCardShape::PORTRAIT,
        );

        $this->assertEmpty($block->wikiIdentifiers());
    }
}
