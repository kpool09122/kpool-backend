<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\Service;

use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Wiki\Application\Service\WikiContentKeyService;
use Source\Wiki\Wiki\Domain\ValueObject\Block\EmbedBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\EmbedProvider;
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
use Source\Wiki\Wiki\Infrastructure\Repository\SectionContentMapper;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class WikiContentKeyServiceTest extends TestCase
{
    public function testFillsOnlyMissingKeysRecursivelyAndIsIdempotent(): void
    {
        $unchangedBlock = new TextBlock(5, 'Kept body', key: 'kept-block');
        $original = new SectionContentCollection([
            new Section('Heading', 7, new SectionContentCollection([
                $unchangedBlock,
                new Section('Child', 2, new SectionContentCollection([new TextBlock(3, 'Child body')]), 2),
            ]), key: 'kept-section'),
        ]);
        /** @var UuidGeneratorInterface&MockInterface $generator */
        $generator = Mockery::mock(UuidGeneratorInterface::class);
        $generator->shouldReceive('generate')->twice()->andReturn('child-body-key', 'child-key');
        $service = new WikiContentKeyService($generator);

        $assigned = $service->assignMissingKeys($original);
        $root = $assigned->sections()[0];
        self::assertSame('kept-section', $root->key());
        self::assertSame('Heading', $root->title());
        self::assertSame(7, $root->displayOrder());
        self::assertSame($unchangedBlock, $root->contents()->blocks()[0]);
        $child = $root->contents()->sections()[0];
        self::assertSame('child-key', $child->key());
        self::assertSame('Child', $child->title());
        self::assertSame(2, $child->depth());
        self::assertSame(2, $child->displayOrder());
        $body = $child->contents()->blocks()[0];
        self::assertInstanceOf(TextBlock::class, $body);
        self::assertSame('child-body-key', $body->key());
        self::assertSame('Child body', $body->content());
        self::assertSame(3, $body->displayOrder());
        self::assertNull($original->sections()[0]->contents()->sections()[0]->key());
        self::assertSame($assigned, $service->assignMissingKeys($assigned));
    }

    public function testAssignsDistinctKeysWithoutChangingAnyBlockData(): void
    {
        $image = new ImageIdentifier(StrTestHelper::generateUuid());
        $blocks = [
            new TextBlock(0, 'Body'),
            new ImageBlock(1, $image, 'Caption', 'Alt', 450),
            new ImageGalleryBlock(2, [$image], 'Gallery', 500),
            new EmbedBlock(3, EmbedProvider::YOUTUBE, 'video', 'Embed'),
            new QuoteBlock(4, 'Quote', 'Source'),
            new ListBlock(5, ListType::BULLET, ['Item']),
            new HistoryBlock(6, [new HistoryEntry(2026, 'Event', 'Detail')]),
            new TableBlock(7, [[new TableCell('Cell', 2)]], [new TableCell('Header')], '400'),
            new ProfileCardListBlock(8, [], ProfileCardShape::SQUARE, 'Profiles'),
        ];
        $service = $this->app()->make(WikiContentKeyService::class);
        $assigned = $service->assignMissingKeys(new SectionContentCollection($blocks));
        $keys = [];
        foreach ($assigned->all() as $index => $block) {
            $keys[] = $block->key();
            self::assertNotNull($block->key());
            self::assertNotSame('', $block->key());
            self::assertNull($blocks[$index]->key());
            $stored = SectionContentMapper::collectionToArray(new SectionContentCollection([$block]));
            unset($stored[0]['block_key']);
            self::assertSame(SectionContentMapper::collectionToArray(new SectionContentCollection([$blocks[$index]])), $stored);
        }
        self::assertCount(count($blocks), array_unique($keys));
    }

    public function testRetainsTheCollectionsTopLevelRestriction(): void
    {
        $service = $this->app()->make(WikiContentKeyService::class);
        $sections = new SectionContentCollection([
            new Section('Heading', 0, new SectionContentCollection()),
        ], allowBlocks: false);
        $assigned = $service->assignMissingKeys($sections);

        $this->expectException(InvalidArgumentException::class);
        $assigned->add(new TextBlock(1, 'Must remain prohibited'));
    }
}
