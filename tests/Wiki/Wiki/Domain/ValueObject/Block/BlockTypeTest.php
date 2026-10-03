<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Block;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Block\BlockType;

class BlockTypeTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'TEXT' => 'text',
            'IMAGE' => 'image',
            'IMAGE_GALLERY' => 'image_gallery',
            'EMBED' => 'embed',
            'QUOTE' => 'quote',
            'LIST' => 'list',
            'TABLE' => 'table',
            'PROFILE_CARD_LIST' => 'profile_card_list',
        ], array_column(BlockType::cases(), 'value', 'name'));
    }
}
