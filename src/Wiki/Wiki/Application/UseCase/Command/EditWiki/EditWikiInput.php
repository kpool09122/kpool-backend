<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\EditWiki;

use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Color;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\MetaDescription;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\SeoKeywords;
use Source\Wiki\Wiki\Domain\ValueObject\SeoTitle;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class EditWikiInput implements EditWikiInputPort
{
    /**
     * @param DraftWikiIdentifier $wikiIdentifier
     * @param BasicInterface $basic
     * @param SectionContentCollection $sections
     * @param Color|null $themeColor
     * @param ImageIdentifier|null $imageIdentifier
     * @param SeoKeywords|null $keywords
     * @param PrincipalIdentifier $principalIdentifier
     * @param ResourceType $resourceType
     * @param WikiIdentifier|null $agencyIdentifier
     * @param WikiIdentifier[] $groupIdentifiers
     * @param WikiIdentifier[] $talentIdentifiers
     */
    public function __construct(
        private DraftWikiIdentifier      $wikiIdentifier,
        private BasicInterface           $basic,
        private SectionContentCollection $sections,
        private ?Color                   $themeColor,
        private PrincipalIdentifier      $principalIdentifier,
        private ResourceType             $resourceType,
        private ?WikiIdentifier          $agencyIdentifier = null,
        private array                    $groupIdentifiers = [],
        private array                    $talentIdentifiers = [],
        private ?ImageIdentifier         $imageIdentifier = null,
        private ?SeoTitle                $title = null,
        private ?MetaDescription         $metaDescription = null,
        private ?SeoKeywords             $keywords = null,
    ) {
    }

    public function wikiIdentifier(): DraftWikiIdentifier
    {
        return $this->wikiIdentifier;
    }

    public function basic(): BasicInterface
    {
        return $this->basic;
    }

    public function sections(): SectionContentCollection
    {
        return $this->sections;
    }

    public function themeColor(): ?Color
    {
        return $this->themeColor;
    }

    public function imageIdentifier(): ?ImageIdentifier
    {
        return $this->imageIdentifier;
    }

    public function title(): ?SeoTitle
    {
        return $this->title;
    }

    public function metaDescription(): ?MetaDescription
    {
        return $this->metaDescription;
    }

    /**
     * @return SeoKeywords|null
     */
    public function keywords(): ?SeoKeywords
    {
        return $this->keywords;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function resourceType(): ResourceType
    {
        return $this->resourceType;
    }

    public function agencyIdentifier(): ?WikiIdentifier
    {
        return $this->agencyIdentifier;
    }

    /** @return WikiIdentifier[] */
    public function groupIdentifiers(): array
    {
        return $this->groupIdentifiers;
    }

    /** @return WikiIdentifier[] */
    public function talentIdentifiers(): array
    {
        return $this->talentIdentifiers;
    }
}
