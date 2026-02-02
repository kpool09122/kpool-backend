<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Section;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TextBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Section\Section;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;

class SectionContentCollectionTest extends TestCase
{
    /**
     * 正常系: 空のコレクションが生成されること
     */
    public function test__constructWithEmpty(): void
    {
        $collection = new SectionContentCollection();

        $this->assertTrue($collection->isEmpty());
        $this->assertSame(0, $collection->count());
        $this->assertEmpty($collection->all());
    }

    /**
     * 正常系: ブロックとセクションを含むコレクションが生成されること
     */
    public function test__constructWithContents(): void
    {
        $block = new TextBlock(displayOrder: 1, content: 'テスト');
        $section = new Section(
            title: 'セクション',
            displayOrder: 2,
            contents: new SectionContentCollection(),
        );

        $collection = new SectionContentCollection([$block, $section]);

        $this->assertSame(2, $collection->count());
        $this->assertFalse($collection->isEmpty());
    }

    /**
     * 正常系: sortedがdisplayOrder順にソートされること
     */
    public function testSorted(): void
    {
        $block1 = new TextBlock(displayOrder: 3, content: '3番目');
        $block2 = new TextBlock(displayOrder: 1, content: '1番目');
        $block3 = new TextBlock(displayOrder: 2, content: '2番目');

        $collection = new SectionContentCollection([$block1, $block2, $block3]);
        $sorted = $collection->sorted();

        $this->assertSame(1, $sorted[0]->displayOrder());
        $this->assertSame(2, $sorted[1]->displayOrder());
        $this->assertSame(3, $sorted[2]->displayOrder());
    }

    /**
     * 正常系: addで新しいコレクションが返されること
     */
    public function testAdd(): void
    {
        $collection = new SectionContentCollection();
        $block = new TextBlock(displayOrder: 1, content: 'テスト');

        $newCollection = $collection->add($block);

        $this->assertSame(0, $collection->count());
        $this->assertSame(1, $newCollection->count());
    }

    /**
     * 正常系: blocksがブロックのみを返すこと
     */
    public function testBlocks(): void
    {
        $block = new TextBlock(displayOrder: 1, content: 'テスト');
        $section = new Section(
            title: 'セクション',
            displayOrder: 2,
            contents: new SectionContentCollection(),
        );

        $collection = new SectionContentCollection([$block, $section]);

        $this->assertCount(1, $collection->blocks());
        $this->assertSame($block, $collection->blocks()[0]);
    }

    /**
     * 正常系: sectionsがセクションのみを返すこと
     */
    public function testSections(): void
    {
        $block = new TextBlock(displayOrder: 1, content: 'テスト');
        $section = new Section(
            title: 'セクション',
            displayOrder: 2,
            contents: new SectionContentCollection(),
        );

        $collection = new SectionContentCollection([$block, $section]);

        $this->assertCount(1, $collection->sections());
        $this->assertSame($section, $collection->sections()[0]);
    }

    /**
     * 異常系: allowBlocks=falseでブロックを渡すと例外がスローされること
     */
    public function testThrowsExceptionWhenBlocksNotAllowed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Blocks are not allowed at the top level.');

        $block = new TextBlock(displayOrder: 1, content: 'テスト');
        new SectionContentCollection([$block], allowBlocks: false);
    }
}
