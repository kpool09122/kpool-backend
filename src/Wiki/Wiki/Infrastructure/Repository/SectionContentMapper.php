<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Repository;

use InvalidArgumentException;
use Source\Shared\Domain\Support\TypedValue;
use Source\Shared\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\Block\BlockInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Block\BlockType;
use Source\Wiki\Wiki\Domain\ValueObject\Block\EmbedBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\EmbedProvider;
use Source\Wiki\Wiki\Domain\ValueObject\Block\GoogleTrendsBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\HistoryBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\HistoryEntry;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ImageBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ImageGalleryBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ListBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ListType;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ProfileCardListBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ProfileCardShape;
use Source\Wiki\Wiki\Domain\ValueObject\Block\QuoteBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TableBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TableCell;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TextBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Section\Section;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentInterface;

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
                        ...($content->key() === null ? [] : ['sectionKey' => $content->key()]),
                        'title' => $content->title(),
                        'displayOrder' => $content->displayOrder(),
                        'contents' => self::collectionToArray($content->contents()),
                    ];
                }

                if ($content instanceof BlockInterface) {
                    return match ($content::class) {
                        TextBlock::class => [
                            'type' => $content->blockType()->value,
                            ...($content->key() === null ? [] : ['blockKey' => $content->key()]),
                            'displayOrder' => $content->displayOrder(),
                            'content' => $content->content(),
                        ],
                        ImageBlock::class => [
                            'type' => $content->blockType()->value,
                            ...($content->key() === null ? [] : ['blockKey' => $content->key()]),
                            'displayOrder' => $content->displayOrder(),
                            'imageIdentifier' => (string) $content->imageIdentifier(),
                            'imageWidth' => $content->imageWidth(),
                            'caption' => $content->caption(),
                            'alt' => $content->alt(),
                        ],
                        ImageGalleryBlock::class => [
                            'imageWidth' => $content->imageWidth(),
                            'type' => $content->blockType()->value,
                            ...($content->key() === null ? [] : ['blockKey' => $content->key()]),
                            'displayOrder' => $content->displayOrder(),
                            'imageIdentifiers' => array_map(
                                static fn (ImageIdentifier $id): string => (string) $id,
                                $content->imageIdentifiers(),
                            ),
                            'caption' => $content->caption(),
                        ],
                        EmbedBlock::class => [
                            'type' => $content->blockType()->value,
                            ...($content->key() === null ? [] : ['blockKey' => $content->key()]),
                            'displayOrder' => $content->displayOrder(),
                            'provider' => $content->provider()->value,
                            'embedId' => $content->embedId(),
                            'caption' => $content->caption(),
                        ],
                        QuoteBlock::class => [
                            'type' => $content->blockType()->value,
                            ...($content->key() === null ? [] : ['blockKey' => $content->key()]),
                            'displayOrder' => $content->displayOrder(),
                            'content' => $content->content(),
                            'source' => $content->source(),
                        ],
                        ListBlock::class => [
                            'type' => $content->blockType()->value,
                            ...($content->key() === null ? [] : ['blockKey' => $content->key()]),
                            'displayOrder' => $content->displayOrder(),
                            'listType' => $content->listType()->value,
                            'items' => $content->items(),
                        ],
                        GoogleTrendsBlock::class => [
                            'type' => $content->blockType()->value,
                            ...($content->key() === null ? [] : ['blockKey' => $content->key()]),
                            'displayOrder' => $content->displayOrder(),
                        ],
                        HistoryBlock::class => [
                            'type' => $content->blockType()->value,
                            ...($content->key() === null ? [] : ['blockKey' => $content->key()]),
                            'displayOrder' => $content->displayOrder(),
                            'entries' => array_map(static fn (HistoryEntry $entry): array => $entry->toArray(), $content->entries()),
                        ],
                        TableBlock::class => [
                            'type' => $content->blockType()->value,
                            ...($content->key() === null ? [] : ['blockKey' => $content->key()]),
                            'displayOrder' => $content->displayOrder(),
                            'headerCells' => self::tableCellsToArray($content->headerCells()),
                            'rowCells' => array_map(
                                static fn (array $rowCells): array => self::tableCellsToArray($rowCells) ?? [],
                                $content->rowCells(),
                            ),
                            'tableWidth' => $content->tableWidth(),
                        ],
                        ProfileCardListBlock::class => [
                            'type' => $content->blockType()->value,
                            ...($content->key() === null ? [] : ['blockKey' => $content->key()]),
                            'displayOrder' => $content->displayOrder(),
                            'wikiIdentifiers' => array_map(
                                static fn (WikiIdentifier $id) => (string) $id,
                                $content->wikiIdentifiers(),
                            ),
                            'cardShape' => $content->cardShape()->value,
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
        return self::collectionFromCanonicalArray(SectionContentJsonNormalizer::normalize($data), $currentDepth);
    }

    /**
     * @param array<array<array-key, mixed>> $data
     */
    private static function collectionFromCanonicalArray(array $data, int $currentDepth): SectionContentCollection
    {
        $contents = array_map(
            static function (array $contentData) use ($currentDepth): SectionContentInterface {
                if (($contentData['type'] ?? '') === 'section') {
                    return new Section(
                        title: TypedValue::string($contentData['title'] ?? ''),
                        displayOrder: TypedValue::int($contentData['displayOrder'] ?? 0),
                        contents: self::collectionFromCanonicalArray(self::arrayRows($contentData['contents'] ?? []), $currentDepth + 1),
                        depth: $currentDepth,
                        key: TypedValue::nullableString($contentData['sectionKey'] ?? null),
                    );
                }

                $blockType = BlockType::from(TypedValue::string($contentData['type'] ?? ''));

                return match ($blockType) {
                    BlockType::TEXT => new TextBlock(
                        displayOrder: TypedValue::int($contentData['displayOrder'] ?? 0),
                        key: TypedValue::nullableString($contentData['blockKey'] ?? null),
                        content: TypedValue::string($contentData['content'] ?? ''),
                    ),
                    BlockType::IMAGE => new ImageBlock(
                        displayOrder: TypedValue::int($contentData['displayOrder'] ?? 0),
                        key: TypedValue::nullableString($contentData['blockKey'] ?? null),
                        imageIdentifier: new ImageIdentifier(TypedValue::string($contentData['imageIdentifier'] ?? '')),
                        caption: TypedValue::nullableString($contentData['caption'] ?? null),
                        alt: TypedValue::nullableString($contentData['alt'] ?? null),
                        imageWidth: TypedValue::nullableInt($contentData['imageWidth'] ?? null),
                    ),
                    BlockType::IMAGE_GALLERY => new ImageGalleryBlock(
                        imageWidth: TypedValue::nullableInt($contentData['imageWidth'] ?? null),
                        displayOrder: TypedValue::int($contentData['displayOrder'] ?? 0),
                        key: TypedValue::nullableString($contentData['blockKey'] ?? null),
                        imageIdentifiers: array_map(
                            static fn (string $id): ImageIdentifier => new ImageIdentifier($id),
                            TypedValue::stringArray($contentData['imageIdentifiers'] ?? []),
                        ),
                        caption: TypedValue::nullableString($contentData['caption'] ?? null),
                    ),
                    BlockType::EMBED => new EmbedBlock(
                        displayOrder: TypedValue::int($contentData['displayOrder'] ?? 0),
                        key: TypedValue::nullableString($contentData['blockKey'] ?? null),
                        provider: EmbedProvider::from(TypedValue::string($contentData['provider'] ?? '')),
                        embedId: TypedValue::string($contentData['embedId'] ?? ''),
                        caption: TypedValue::nullableString($contentData['caption'] ?? null),
                    ),
                    BlockType::QUOTE => new QuoteBlock(
                        displayOrder: TypedValue::int($contentData['displayOrder'] ?? 0),
                        key: TypedValue::nullableString($contentData['blockKey'] ?? null),
                        content: TypedValue::string($contentData['content'] ?? ''),
                        source: TypedValue::nullableString($contentData['source'] ?? null),
                    ),
                    BlockType::LIST => new ListBlock(
                        displayOrder: TypedValue::int($contentData['displayOrder'] ?? 0),
                        key: TypedValue::nullableString($contentData['blockKey'] ?? null),
                        listType: ListType::from(TypedValue::string($contentData['listType'] ?? 'bullet')),
                        items: TypedValue::stringArray($contentData['items'] ?? []),
                    ),
                    BlockType::GOOGLE_TRENDS => new GoogleTrendsBlock(
                        displayOrder: TypedValue::int($contentData['displayOrder'] ?? 0),
                        key: TypedValue::nullableString($contentData['blockKey'] ?? null),
                    ),
                    BlockType::HISTORY => new HistoryBlock(
                        displayOrder: TypedValue::int($contentData['displayOrder'] ?? 0),
                        key: TypedValue::nullableString($contentData['blockKey'] ?? null),
                        entries: array_values(array_map(
                            static fn (array $entry): HistoryEntry => new HistoryEntry(
                                year: TypedValue::int($entry['year'] ?? null),
                                title: TypedValue::string($entry['title'] ?? null),
                                detail: TypedValue::nullableString($entry['detail'] ?? null),
                            ),
                            self::arrayRows($contentData['entries'] ?? []),
                        )),
                    ),
                    BlockType::TABLE => new TableBlock(
                        displayOrder: TypedValue::int($contentData['displayOrder'] ?? 0),
                        key: TypedValue::nullableString($contentData['blockKey'] ?? null),
                        rowCells: array_map(
                            static fn (mixed $rowCells): array => self::tableCellsFromArray($rowCells) ?? [],
                            TypedValue::array($contentData['rowCells'] ?? []),
                        ),
                        headerCells: self::tableCellsFromArray($contentData['headerCells'] ?? null),
                        tableWidth: TypedValue::nullableString($contentData['tableWidth'] ?? null),
                    ),
                    BlockType::PROFILE_CARD_LIST => new ProfileCardListBlock(
                        displayOrder: TypedValue::int($contentData['displayOrder'] ?? 0),
                        key: TypedValue::nullableString($contentData['blockKey'] ?? null),
                        wikiIdentifiers: array_map(
                            static fn (string $id) => new WikiIdentifier($id),
                            TypedValue::stringArray($contentData['wikiIdentifiers'] ?? []),
                        ),
                        cardShape: ProfileCardShape::fromValue($contentData['cardShape'] ?? null),
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
