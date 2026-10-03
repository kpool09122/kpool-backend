<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\Service;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Application\Service\TranslatedWikiData;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;

class TranslatedWikiDataTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $translatedBasic = $this->createStub(BasicInterface::class);
        $translatedSections = new SectionContentCollection([], true);

        $subject = new TranslatedWikiData($translatedBasic, $translatedSections);

        $this->assertSame($translatedBasic, $subject->translatedBasic());
        $this->assertSame($translatedSections, $subject->translatedSections());
    }
}
