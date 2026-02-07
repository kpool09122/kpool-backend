<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\Service;

use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;

readonly class TranslatedWikiData
{
    public function __construct(
        private BasicInterface $translatedBasic,
        private SectionContentCollection $translatedSections,
    ) {
    }

    public function translatedBasic(): BasicInterface
    {
        return $this->translatedBasic;
    }

    public function translatedSections(): SectionContentCollection
    {
        return $this->translatedSections;
    }
}
