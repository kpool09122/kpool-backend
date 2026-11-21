<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Repository;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Infrastructure\Repository\SectionContentJsonNormalizer;

class SectionContentJsonNormalizerTest extends TestCase
{
    public function testCanonicalFieldsTakePrecedenceIncludingExplicitNullAndEmptyArrays(): void
    {
        $canonical = [[
            'type' => 'profile_card_list', 'blockKey' => 'preserved', 'title' => null,
            'wikiIdentifiers' => [], 'cardShape' => null,
        ]];
        $mixed = [[
            ...$canonical[0],
            'block_type' => 'text', 'block_key' => 'old',
            'wiki_identifiers' => ['old-related-wiki'], 'card_shape' => 'square',
        ]];

        self::assertSame($canonical, SectionContentJsonNormalizer::normalize($mixed));
        self::assertSame($canonical, SectionContentJsonNormalizer::normalize($canonical));
    }

    public function testNormalizesMediaFieldsAndPreservesUnknownMetadata(): void
    {
        $legacy = [[
            'type' => 'section', 'section_key' => 'media', 'contents' => [
                ['block_type' => 'image', 'block_key' => 'image', 'image_identifier' => 'image-id', 'image_width' => null],
                ['block_type' => 'image_gallery', 'image_identifiers' => ['image-id'], 'image_width' => 700],
                ['block_type' => 'embed', 'embed_id' => 'video-id', 'caption' => 'image_identifier'],
                ['block_type' => 'list', 'list_type' => 'bullet', 'items' => ['block_key']],
                ['blockType' => 'text', 'content' => 'Text', 'custom_metadata' => ['original_field' => true]],
            ],
        ]];

        $normalized = SectionContentJsonNormalizer::normalize($legacy);

        self::assertEquals([[
            'type' => 'section', 'sectionKey' => 'media', 'contents' => [
                ['type' => 'image', 'blockKey' => 'image', 'imageIdentifier' => 'image-id', 'imageWidth' => null],
                ['type' => 'image_gallery', 'imageIdentifiers' => ['image-id'], 'imageWidth' => 700],
                ['type' => 'embed', 'embedId' => 'video-id', 'caption' => 'image_identifier'],
                ['type' => 'list', 'listType' => 'bullet', 'items' => ['block_key']],
                ['type' => 'text', 'content' => 'Text', 'custom_metadata' => ['original_field' => true]],
            ],
        ]], $normalized);
        self::assertSame($normalized, SectionContentJsonNormalizer::normalize($normalized));
    }
}
