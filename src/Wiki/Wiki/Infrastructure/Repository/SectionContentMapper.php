<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Repository;

use InvalidArgumentException;
use Source\Shared\Domain\Support\TypedValue;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\Block\BlockInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Block\BlockType;
use Source\Wiki\Wiki\Domain\ValueObject\Block\EmbedBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\EmbedProvider;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ImageBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ImageGalleryBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ListBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ListType;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ProfileCardListBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\QuoteBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TableBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TableCell;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TextBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Section\Section;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentInterface;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

final class SectionContentMapper
{
    /**
     * @return array<array<string, mixed>>
     */
    public static function collectionToArray(SectionContentCollection $collection): array
    {
        $sorted = $collection->sorted();

        return array_map(
            static function (SectionContentInterface $content): array {
                if ($content instanceof Section) {
                    return [
                        'type' => 'section',
                        'title' => $content->title(),
                        'display_order' => $content->displayOrder(),
                        'contents' => self::collectionToArray($content->contents()),
                    ];
                }

                if ($content instanceof BlockInterface) {
                    return match ($content::class) {
                        TextBlock::class => [
                            'block_type' => $content->blockType()->value,
                            'display_order' => $content->displayOrder(),
                            'content' => $content->content(),
                        ],
                        ImageBlock::class => [
                            'block_type' => $content->blockType()->value,
                            'display_order' => $content->displayOrder(),
                            'image_identifier' => (string) $content->imageIdentifier(),
                            'caption' => $content->caption(),
                            'alt' => $content->alt(),
                        ],
                        ImageGalleryBlock::class => [
                            'block_type' => $content->blockType()->value,
                            'display_order' => $content->displayOrder(),
                            'image_identifiers' => array_map(
                                static fn (ImageIdentifier $id): string => (string) $id,
                                $content->imageIdentifiers(),
                            ),
                            'caption' => $content->caption(),
                        ],
                        EmbedBlock::class => [
                            'block_type' => $content->blockType()->value,
                            'display_order' => $content->displayOrder(),
                            'provider' => $content->provider()->value,
                            'embed_id' => $content->embedId(),
                            'caption' => $content->caption(),
                        ],
                        QuoteBlock::class => [
                            'block_type' => $content->blockType()->value,
                            'display_order' => $content->displayOrder(),
                            'content' => $content->content(),
                            'source' => $content->source(),
                        ],
                        ListBlock::class => [
                            'block_type' => $content->blockType()->value,
                            'display_order' => $content->displayOrder(),
                            'list_type' => $content->listType()->value,
                            'items' => $content->items(),
                        ],
                        TableBlock::class => [
                            'block_type' => $content->blockType()->value,
                            'display_order' => $content->displayOrder(),
                            'header_cells' => self::tableCellsToArray($content->headerCells()),
                            'row_cells' => array_map(
                                static fn (array $rowCells): array => self::tableCellsToArray($rowCells) ?? [],
                                $content->rowCells(),
                            ),
                            'table_width' => $content->tableWidth(),
                        ],
                        ProfileCardListBlock::class => [
                            'block_type' => $content->blockType()->value,
                            'display_order' => $content->displayOrder(),
                            'wiki_identifiers' => array_map(
                                static fn (WikiIdentifier $id) => (string) $id,
                                $content->wikiIdentifiers(),
                            ),
                            'title' => $content->title(),
                        ],
                        default => throw new InvalidArgumentException('Unknown block type: ' . $content::class),
                    };
                }

                throw new InvalidArgumentException('Unknown content type');
            },
            $sorted
        );
    }

    /**
     * @param array<array<array-key, mixed>> $data
     */
    public static function collectionFromArray(array $data, int $currentDepth = 1): SectionContentCollection
    {
        $contents = array_map(
            static function (array $contentData) use ($currentDepth): SectionContentInterface {
                if (($contentData['type'] ?? '') === 'section') {
                    return new Section(
                        title: TypedValue::string($contentData['title'] ?? ''),
                        displayOrder: TypedValue::int($contentData['display_order'] ?? 0),
                        contents: self::collectionFromArray(self::arrayRows($contentData['contents'] ?? []), $currentDepth + 1),
                        depth: $currentDepth,
                    );
                }

                $blockType = BlockType::from(TypedValue::string($contentData['block_type'] ?? ''));

                return match ($blockType) {
                    BlockType::TEXT => new TextBlock(
                        displayOrder: TypedValue::int($contentData['display_order'] ?? 0),
                        content: TypedValue::string($contentData['content'] ?? ''),
                    ),
                    BlockType::IMAGE => new ImageBlock(
                        displayOrder: TypedValue::int($contentData['display_order'] ?? 0),
                        imageIdentifier: new ImageIdentifier(TypedValue::string($contentData['image_identifier'] ?? '')),
                        caption: TypedValue::nullableString($contentData['caption'] ?? null),
                        alt: TypedValue::nullableString($contentData['alt'] ?? null),
                    ),
                    BlockType::IMAGE_GALLERY => new ImageGalleryBlock(
                        displayOrder: TypedValue::int($contentData['display_order'] ?? 0),
                        imageIdentifiers: array_map(
                            static fn (string $id): ImageIdentifier => new ImageIdentifier($id),
                            TypedValue::stringArray($contentData['image_identifiers'] ?? []),
                        ),
                        caption: TypedValue::nullableString($contentData['caption'] ?? null),
                    ),
                    BlockType::EMBED => new EmbedBlock(
                        displayOrder: TypedValue::int($contentData['display_order'] ?? 0),
                        provider: EmbedProvider::from(TypedValue::string($contentData['provider'] ?? '')),
                        embedId: TypedValue::string($contentData['embed_id'] ?? ''),
                        caption: TypedValue::nullableString($contentData['caption'] ?? null),
                    ),
                    BlockType::QUOTE => new QuoteBlock(
                        displayOrder: TypedValue::int($contentData['display_order'] ?? 0),
                        content: TypedValue::string($contentData['content'] ?? ''),
                        source: TypedValue::nullableString($contentData['source'] ?? null),
                    ),
                    BlockType::LIST => new ListBlock(
                        displayOrder: TypedValue::int($contentData['display_order'] ?? 0),
                        listType: ListType::from(TypedValue::string($contentData['list_type'] ?? 'bullet')),
                        items: TypedValue::stringArray($contentData['items'] ?? []),
                    ),
                    BlockType::TABLE => new TableBlock(
                        displayOrder: TypedValue::int($contentData['display_order'] ?? 0),
                        rowCells: array_map(
                            static fn (mixed $rowCells): array => self::tableCellsFromArray($rowCells) ?? [],
                            TypedValue::array($contentData['row_cells'] ?? []),
                        ),
                        headerCells: self::tableCellsFromArray($contentData['header_cells'] ?? null),
                        tableWidth: TypedValue::nullableString($contentData['table_width'] ?? null),
                    ),
                    BlockType::PROFILE_CARD_LIST => new ProfileCardListBlock(
                        displayOrder: TypedValue::int($contentData['display_order'] ?? 0),
                        wikiIdentifiers: array_map(
                            static fn (string $id) => new WikiIdentifier($id),
                            TypedValue::stringArray($contentData['wiki_identifiers'] ?? []),
                        ),
                        title: TypedValue::nullableString($contentData['title'] ?? null),
                    ),
                };
            },
            $data
        );

        return new SectionContentCollection($contents);
    }

    /**
     * @param array<TableCell>|null $cells
     * @return array<array{content: string, colspan?: int}>|null
     */
    private static function tableCellsToArray(?array $cells): ?array
    {
        if ($cells === null) {
            return null;
        }

        return array_map(
            static fn (TableCell $cell): array => $cell->toArray(),
            $cells,
        );
    }

    /** @return array<TableCell>|null */
    private static function tableCellsFromArray(mixed $cells): ?array
    {
        if ($cells === null) {
            return null;
        }

        return array_map(
            static function (mixed $cell): TableCell {
                $cell = TypedValue::array($cell);

                return new TableCell(
                    content: TypedValue::string($cell['content'] ?? ''),
                    colspan: TypedValue::nullableInt($cell['colspan'] ?? null),
                );
            },
            TypedValue::array($cells),
        );
    }

    /** @return array<array<array-key, mixed>> */
    private static function arrayRows(mixed $value): array
    {
        return array_map(TypedValue::array(...), TypedValue::array($value));
    }
}
