<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Section;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Section\Section;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;

class SectionTest extends TestCase
{
    /**
     * 正常系: 全ての値を指定してインスタンスが生成されること
     */
    public function test__construct(): void
    {
        $title = 'プロフィール';
        $displayOrder = 1;
        $contents = new SectionContentCollection();
        $depth = 1;

        $section = new Section(
            title: $title,
            displayOrder: $displayOrder,
            contents: $contents,
            depth: $depth,
        );

        $this->assertSame($title, $section->title());
        $this->assertSame($displayOrder, $section->displayOrder());
        $this->assertSame($contents, $section->contents());
        $this->assertSame($depth, $section->depth());
    }

    /**
     * 正常系: depthのデフォルト値が1であること
     */
    public function test__constructWithDefaultDepth(): void
    {
        $section = new Section(
            title: 'セクション',
            displayOrder: 0,
            contents: new SectionContentCollection(),
        );

        $this->assertSame(1, $section->depth());
    }

    /**
     * 正常系: 最大深度でインスタンスが生成されること
     */
    public function test__constructWithMaxDepth(): void
    {
        $section = new Section(
            title: 'セクション',
            displayOrder: 0,
            contents: new SectionContentCollection(),
            depth: Section::MAX_DEPTH,
        );

        $this->assertSame(Section::MAX_DEPTH, $section->depth());
    }

    /**
     * 異常系: 最大深度を超えた場合、例外がスローされること
     */
    public function testThrowsExceptionWhenExceedingMaxDepth(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Section(
            title: 'セクション',
            displayOrder: 0,
            contents: new SectionContentCollection(),
            depth: Section::MAX_DEPTH + 1,
        );
    }
}
