<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Repository;

use Source\Shared\Domain\Support\TypedValue;

final class SectionContentJsonNormalizer
{
    private const array LEGACY_FIELDS = [
        'section_key' => 'sectionKey',
        'block_key' => 'blockKey',
        'block_type' => 'type',
        'blockType' => 'type',
        'display_order' => 'displayOrder',
        'image_identifier' => 'imageIdentifier',
        'image_identifiers' => 'imageIdentifiers',
        'image_width' => 'imageWidth',
        'embed_id' => 'embedId',
        'list_type' => 'listType',
        'header_cells' => 'headerCells',
        'row_cells' => 'rowCells',
        'table_width' => 'tableWidth',
        'wiki_identifiers' => 'wikiIdentifiers',
        'card_shape' => 'cardShape',
        'related_resource_type' => 'relatedResourceType',
    ];

    /**
     * @param array<array<array-key, mixed>> $contents
     * @return list<array<array-key, mixed>>
     */
    public static function normalize(array $contents): array
    {
        return array_values(array_map(static function (array $content): array {
            foreach (self::LEGACY_FIELDS as $legacy => $canonical) {
                if (array_key_exists($legacy, $content)) {
                    if (! array_key_exists($canonical, $content)) {
                        $content[$canonical] = $content[$legacy];
                    }
                    unset($content[$legacy]);
                }
            }

            if (($content['type'] ?? null) === 'section' && is_array($content['contents'] ?? null)) {
                $content['contents'] = self::normalize(array_map(TypedValue::array(...), $content['contents']));
            }

            return $content;
        }, $contents));
    }
}
