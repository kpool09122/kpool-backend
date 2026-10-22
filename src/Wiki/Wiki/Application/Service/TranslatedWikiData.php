<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\Service;

use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\MetaDescription;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\SeoKeywords;
use Source\Wiki\Wiki\Domain\ValueObject\SeoTitle;

readonly class TranslatedWikiData
{
    public function __construct(
        private BasicInterface $translatedBasic,
        private SectionContentCollection $translatedSections,
        private ?SeoTitle $translatedTitle = null,
        private ?MetaDescription $translatedMetaDescription = null,
        private ?SeoKeywords $translatedKeywords = null,
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

    public function translatedTitle(): ?SeoTitle
    {
        return $this->translatedTitle;
    }

    public function translatedMetaDescription(): ?MetaDescription
    {
        return $this->translatedMetaDescription;
    }

    public function translatedKeywords(): ?SeoKeywords
    {
        return $this->translatedKeywords;
    }
}
