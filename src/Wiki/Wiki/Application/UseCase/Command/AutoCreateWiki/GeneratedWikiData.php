<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\AutoCreateWiki;

use Source\Wiki\Shared\Application\DTO\SourceReference;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\MetaDescription;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\SeoKeywords;
use Source\Wiki\Wiki\Domain\ValueObject\SeoTitle;

final readonly class GeneratedWikiData
{
    /**
     * @param string|null $alphabetName
     * @param BasicInterface $basic
     * @param SectionContentCollection $sections
     * @param SourceReference[] $sources
     */
    public function __construct(
        private ?string                  $alphabetName,
        private BasicInterface           $basic,
        private SectionContentCollection $sections,
        private array                    $sources,
        private ?SeoTitle $title = null,
        private ?MetaDescription $metaDescription = null,
        private ?SeoKeywords $keywords = null,
    ) {
    }

    public function title(): ?SeoTitle
    {
        return $this->title;
    }

    public function metaDescription(): ?MetaDescription
    {
        return $this->metaDescription;
    }

    public function keywords(): ?SeoKeywords
    {
        return $this->keywords;
    }

    public function alphabetName(): ?string
    {
        return $this->alphabetName;
    }

    public function basic(): BasicInterface
    {
        return $this->basic;
    }

    public function sections(): SectionContentCollection
    {
        return $this->sections;
    }

    /**
     * @return SourceReference[]
     */
    public function sources(): array
    {
        return $this->sources;
    }
}
