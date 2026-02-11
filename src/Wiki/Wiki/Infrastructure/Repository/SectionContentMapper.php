<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Repository;

use InvalidArgumentException;
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
                            'image_identifier' => $content->imageIdentifier(),
                            'caption' => $content->caption(),
                            'alt' => $content->alt(),
                        ],
                        ImageGalleryBlock::class => [
                            'block_type' => $content->blockType()->value,
                            'display_order' => $content->displayOrder(),
                            'image_identifiers' => $content->imageIdentifiers(),
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
                            'rows' => $content->rows(),
                            'headers' => $content->headers(),
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
     * @param array<array<string, mixed>> $data
     */
    public static function collectionFromArray(array $data, int $currentDepth = 1): SectionContentCollection
    {
        $contents = array_map(
            static function (array $contentData) use ($currentDepth): SectionContentInterface {
                if (($contentData['type'] ?? '') === 'section') {
                    return new Section(
                        title: $contentData['title'] ?? '',
                        displayOrder: $contentData['display_order'] ?? 0,
                        contents: self::collectionFromArray($contentData['contents'] ?? [], $currentDepth + 1),
                        depth: $currentDepth,
                    );
                }

                $blockType = BlockType::from($contentData['block_type'] ?? '');

                return match ($blockType) {
                    BlockType::TEXT => new TextBlock(
                        displayOrder: $contentData['display_order'] ?? 0,
                        content: $contentData['content'] ?? '',
                    ),
                    BlockType::IMAGE => new ImageBlock(
                        displayOrder: $contentData['display_order'] ?? 0,
                        imageIdentifier: $contentData['image_identifier'] ?? '',
                        caption: $contentData['caption'] ?? null,
                        alt: $contentData['alt'] ?? null,
                    ),
                    BlockType::IMAGE_GALLERY => new ImageGalleryBlock(
                        displayOrder: $contentData['display_order'] ?? 0,
                        imageIdentifiers: $contentData['image_identifiers'] ?? [],
                        caption: $contentData['caption'] ?? null,
                    ),
                    BlockType::EMBED => new EmbedBlock(
                        displayOrder: $contentData['display_order'] ?? 0,
                        provider: EmbedProvider::from($contentData['provider'] ?? ''),
                        embedId: $contentData['embed_id'] ?? '',
                        caption: $contentData['caption'] ?? null,
                    ),
                    BlockType::QUOTE => new QuoteBlock(
                        displayOrder: $contentData['display_order'] ?? 0,
                        content: $contentData['content'] ?? '',
                        source: $contentData['source'] ?? null,
                    ),
                    BlockType::LIST => new ListBlock(
                        displayOrder: $contentData['display_order'] ?? 0,
                        listType: ListType::from($contentData['list_type'] ?? 'bullet'),
                        items: $contentData['items'] ?? [],
                    ),
                    BlockType::TABLE => new TableBlock(
                        displayOrder: $contentData['display_order'] ?? 0,
                        rows: $contentData['rows'] ?? [],
                        headers: $contentData['headers'] ?? null,
                    ),
                    BlockType::PROFILE_CARD_LIST => new ProfileCardListBlock(
                        displayOrder: $contentData['display_order'] ?? 0,
                        wikiIdentifiers: array_map(
                            static fn (string $id) => new WikiIdentifier($id),
                            $contentData['wiki_identifiers'] ?? [],
                        ),
                        title: $contentData['title'] ?? null,
                    ),
                };
            },
            $data
        );

        return new SectionContentCollection($contents);
    }
}
