<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Service;

use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ImageBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TextBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Section\Section;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Infrastructure\Service\SectionTranslationFingerprint;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SectionTranslationFingerprintTest extends TestCase
{
    public function testIgnoresNonTranslatedDataAndNestedIndependentSections(): void
    {
        $first = new Section('Heading', 0, new SectionContentCollection([
            new ImageBlock(0, new ImageIdentifier(StrTestHelper::generateUuid()), 'Caption', 'Alt'),
            new Section('Old child', 1, new SectionContentCollection([new TextBlock(0, 'Old text')]), 2),
        ]));
        $second = new Section('Heading', 7, new SectionContentCollection([
            new ImageBlock(0, new ImageIdentifier(StrTestHelper::generateUuid()), 'Caption', 'Alt'),
            new Section('New child', 1, new SectionContentCollection([new TextBlock(0, 'New text')]), 2),
        ]), 3);
        self::assertSame(SectionTranslationFingerprint::of($first), SectionTranslationFingerprint::of($second));
    }

    public function testDetectsChangedTextAndSlotStructure(): void
    {
        $first = new Section('Heading', 0, new SectionContentCollection([new TextBlock(0, 'Original')]));
        $changed = new Section('Heading', 0, new SectionContentCollection([new TextBlock(0, 'Changed')]));
        $renamed = new Section('Renamed', 0, $first->contents());
        self::assertNotSame(SectionTranslationFingerprint::of($first), SectionTranslationFingerprint::of($changed));
        self::assertNotSame(SectionTranslationFingerprint::of($first), SectionTranslationFingerprint::of($renamed));
        $image = new ImageIdentifier(StrTestHelper::generateUuid());
        self::assertNotSame(
            SectionTranslationFingerprint::of(new ImageBlock(0, $image, 'Text', null)),
            SectionTranslationFingerprint::of(new ImageBlock(0, $image, null, 'Text')),
        );
    }
}
