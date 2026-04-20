<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Repository;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
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
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Wiki\Infrastructure\Repository\SectionContentMapper;
use Tests\Helper\StrTestHelper;

class SectionContentMapperTest extends TestCase
{
    /**
     * 正常系: TextBlockが正しく変換・復元されること.
     */
    public function testTextBlockRoundTrip(): void
    {
        $collection = new SectionContentCollection([
            new TextBlock(displayOrder: 1, content: 'Hello World'),
        ]);

        $array = SectionContentMapper::collectionToArray($collection);
        $restored = SectionContentMapper::collectionFromArray($array);

        $this->assertCount(1, $restored->sorted());
        $block = $restored->sorted()[0];
        $this->assertInstanceOf(TextBlock::class, $block);
        $this->assertSame(1, $block->displayOrder());
        $this->assertSame('Hello World', $block->content());
    }

    /**
     * 正常系: ImageBlockが正しく変換・復元されること.
     */
    public function testImageBlockRoundTrip(): void
    {
        $imageId = StrTestHelper::generateUuid();
        $collection = new SectionContentCollection([
            new ImageBlock(displayOrder: 1, imageIdentifier: new ImageIdentifier($imageId), caption: 'キャプション', alt: '代替テキスト'),
        ]);

        $array = SectionContentMapper::collectionToArray($collection);
        $restored = SectionContentMapper::collectionFromArray($array);

        $block = $restored->sorted()[0];
        $this->assertInstanceOf(ImageBlock::class, $block);
        $this->assertSame(1, $block->displayOrder());
        $this->assertSame('キャプション', $block->caption());
        $this->assertSame('代替テキスト', $block->alt());
    }

    /**
     * 正常系: ImageGalleryBlockが正しく変換・復元されること.
     */
    public function testImageGalleryBlockRoundTrip(): void
    {
        $imageId1 = StrTestHelper::generateUuid();
        $imageId2 = StrTestHelper::generateUuid();
        $collection = new SectionContentCollection([
            new ImageGalleryBlock(
                displayOrder: 1,
                imageIdentifiers: [new ImageIdentifier($imageId1), new ImageIdentifier($imageId2)],
                caption: 'ギャラリー',
            ),
        ]);

        $array = SectionContentMapper::collectionToArray($collection);
        $restored = SectionContentMapper::collectionFromArray($array);

        $block = $restored->sorted()[0];
        $this->assertInstanceOf(ImageGalleryBlock::class, $block);
        $this->assertCount(2, $block->imageIdentifiers());
        $this->assertSame('ギャラリー', $block->caption());
    }

    /**
     * 正常系: EmbedBlockが正しく変換・復元されること.
     */
    public function testEmbedBlockRoundTrip(): void
    {
        $collection = new SectionContentCollection([
            new EmbedBlock(displayOrder: 1, provider: EmbedProvider::YOUTUBE, embedId: 'abc123', caption: '動画'),
        ]);

        $array = SectionContentMapper::collectionToArray($collection);
        $restored = SectionContentMapper::collectionFromArray($array);

        $block = $restored->sorted()[0];
        $this->assertInstanceOf(EmbedBlock::class, $block);
        $this->assertSame(EmbedProvider::YOUTUBE, $block->provider());
        $this->assertSame('abc123', $block->embedId());
        $this->assertSame('動画', $block->caption());
    }

    /**
     * 正常系: QuoteBlockが正しく変換・復元されること.
     */
    public function testQuoteBlockRoundTrip(): void
    {
        $collection = new SectionContentCollection([
            new QuoteBlock(displayOrder: 1, content: '引用文', source: '出典元'),
        ]);

        $array = SectionContentMapper::collectionToArray($collection);
        $restored = SectionContentMapper::collectionFromArray($array);

        $block = $restored->sorted()[0];
        $this->assertInstanceOf(QuoteBlock::class, $block);
        $this->assertSame('引用文', $block->content());
        $this->assertSame('出典元', $block->source());
    }

    /**
     * 正常系: ListBlockが正しく変換・復元されること.
     */
    public function testListBlockRoundTrip(): void
    {
        $collection = new SectionContentCollection([
            new ListBlock(displayOrder: 1, listType: ListType::BULLET, items: ['項目1', '項目2', '項目3']),
        ]);

        $array = SectionContentMapper::collectionToArray($collection);
        $restored = SectionContentMapper::collectionFromArray($array);

        $block = $restored->sorted()[0];
        $this->assertInstanceOf(ListBlock::class, $block);
        $this->assertSame(ListType::BULLET, $block->listType());
        $this->assertSame(['項目1', '項目2', '項目3'], $block->items());
    }

    /**
     * 正常系: TableBlockが正しく変換・復元されること.
     */
    public function testTableBlockRoundTrip(): void
    {
        $collection = new SectionContentCollection([
            new TableBlock(
                displayOrder: 1,
                rowCells: [
                    [new TableCell('A1'), new TableCell('B1', 2)],
                    [new TableCell('A2'), new TableCell('B2')],
                ],
                headerCells: [new TableCell('列A'), new TableCell('列B')],
                tableWidth: '640px',
            ),
        ]);

        $array = SectionContentMapper::collectionToArray($collection);
        $restored = SectionContentMapper::collectionFromArray($array);

        $block = $restored->sorted()[0];
        $this->assertInstanceOf(TableBlock::class, $block);
        $this->assertEquals(
            [
                [new TableCell('A1'), new TableCell('B1', 2)],
                [new TableCell('A2'), new TableCell('B2')],
            ],
            $block->rowCells()
        );
        $this->assertEquals([new TableCell('列A'), new TableCell('列B')], $block->headerCells());
        $this->assertSame('640px', $block->tableWidth());
    }

    /**
     * 正常系: ProfileCardListBlockが正しく変換・復元されること.
     */
    public function testProfileCardListBlockRoundTrip(): void
    {
        $wikiId1 = StrTestHelper::generateUuid();
        $wikiId2 = StrTestHelper::generateUuid();
        $collection = new SectionContentCollection([
            new ProfileCardListBlock(
                displayOrder: 1,
                wikiIdentifiers: [new WikiIdentifier($wikiId1), new WikiIdentifier($wikiId2)],
                title: 'メンバー',
            ),
        ]);

        $array = SectionContentMapper::collectionToArray($collection);
        $restored = SectionContentMapper::collectionFromArray($array);

        $block = $restored->sorted()[0];
        $this->assertInstanceOf(ProfileCardListBlock::class, $block);
        $this->assertCount(2, $block->wikiIdentifiers());
        $this->assertSame($wikiId1, (string) $block->wikiIdentifiers()[0]);
        $this->assertSame($wikiId2, (string) $block->wikiIdentifiers()[1]);
        $this->assertSame('メンバー', $block->title());
    }

    /**
     * 正常系: Sectionが正しく変換・復元されること.
     */
    public function testSectionRoundTrip(): void
    {
        $collection = new SectionContentCollection([
            new Section(
                title: 'セクション1',
                displayOrder: 1,
                contents: new SectionContentCollection([
                    new TextBlock(displayOrder: 1, content: 'セクション内テキスト'),
                ]),
                depth: 1,
            ),
        ]);

        $array = SectionContentMapper::collectionToArray($collection);
        $restored = SectionContentMapper::collectionFromArray($array);

        $section = $restored->sorted()[0];
        $this->assertInstanceOf(Section::class, $section);
        $this->assertSame('セクション1', $section->title());
        $this->assertSame(1, $section->displayOrder());

        $innerBlock = $section->contents()->sorted()[0];
        $this->assertInstanceOf(TextBlock::class, $innerBlock);
        $this->assertSame('セクション内テキスト', $innerBlock->content());
    }

    /**
     * 正常系: 複数ブロックが混在するコレクションが正しく変換・復元されること.
     */
    public function testMixedBlocksRoundTrip(): void
    {
        $collection = new SectionContentCollection([
            new TextBlock(displayOrder: 1, content: 'テキスト'),
            new QuoteBlock(displayOrder: 2, content: '引用'),
            new ListBlock(displayOrder: 3, listType: ListType::NUMBERED, items: ['a', 'b']),
        ]);

        $array = SectionContentMapper::collectionToArray($collection);
        $restored = SectionContentMapper::collectionFromArray($array);

        $sorted = $restored->sorted();
        $this->assertCount(3, $sorted);
        $this->assertInstanceOf(TextBlock::class, $sorted[0]);
        $this->assertInstanceOf(QuoteBlock::class, $sorted[1]);
        $this->assertInstanceOf(ListBlock::class, $sorted[2]);
    }

    /**
     * 正常系: 空のコレクションが正しく変換・復元されること.
     */
    public function testEmptyCollectionRoundTrip(): void
    {
        $collection = new SectionContentCollection([]);

        $array = SectionContentMapper::collectionToArray($collection);
        $restored = SectionContentMapper::collectionFromArray($array);

        $this->assertSame([], $array);
        $this->assertCount(0, $restored->sorted());
    }

    /**
     * 正常系: ネストしたSectionが正しく変換・復元されること.
     */
    public function testNestedSectionRoundTrip(): void
    {
        $collection = new SectionContentCollection([
            new Section(
                title: '親セクション',
                displayOrder: 1,
                contents: new SectionContentCollection([
                    new Section(
                        title: '子セクション',
                        displayOrder: 1,
                        contents: new SectionContentCollection([
                            new TextBlock(displayOrder: 1, content: 'ネスト内テキスト'),
                        ]),
                        depth: 2,
                    ),
                ]),
                depth: 1,
            ),
        ]);

        $array = SectionContentMapper::collectionToArray($collection);
        $restored = SectionContentMapper::collectionFromArray($array);

        $parentSection = $restored->sorted()[0];
        $this->assertInstanceOf(Section::class, $parentSection);
        $this->assertSame('親セクション', $parentSection->title());

        $childSection = $parentSection->contents()->sorted()[0];
        $this->assertInstanceOf(Section::class, $childSection);
        $this->assertSame('子セクション', $childSection->title());

        $textBlock = $childSection->contents()->sorted()[0];
        $this->assertInstanceOf(TextBlock::class, $textBlock);
        $this->assertSame('ネスト内テキスト', $textBlock->content());
    }
}
