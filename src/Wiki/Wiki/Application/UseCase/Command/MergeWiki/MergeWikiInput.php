<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\MergeWiki;

use DateTimeImmutable;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\HexColor;
use Source\Wiki\Wiki\Domain\ValueObject\MetaDescription;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\SeoKeywords;
use Source\Wiki\Wiki\Domain\ValueObject\SeoTitle;
use Source\Wiki\Wiki\Domain\ValueObject\WikiFontStyle;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class MergeWikiInput implements MergeWikiInputPort
{
    /**
     * @param DraftWikiIdentifier $wikiIdentifier
     * @param BasicInterface $basic
     * @param SectionContentCollection $sections
     * @param HexColor|null $themeColor
     * @param WikiFontStyle|null $fontStyle
     * @param ImageIdentifier|null $imageIdentifier
     * @param SeoKeywords|null $keywords
     * @param PrincipalIdentifier $principalIdentifier
     * @param ResourceType $resourceType
     * @param DateTimeImmutable $mergedAt
     * @param WikiIdentifier|null $agencyIdentifier
     * @param WikiIdentifier[] $groupIdentifiers
     * @param WikiIdentifier[] $talentIdentifiers
     */
    public function __construct(
        private DraftWikiIdentifier      $wikiIdentifier,
        private BasicInterface           $basic,
        private SectionContentCollection $sections,
        private ?HexColor                   $themeColor,
        private PrincipalIdentifier      $principalIdentifier,
        private ResourceType             $resourceType,
        private DateTimeImmutable        $mergedAt,
        private ?WikiIdentifier          $agencyIdentifier = null,
        private array                    $groupIdentifiers = [],
        private array                    $talentIdentifiers = [],
        private ?ImageIdentifier         $imageIdentifier = null,
        private ?SeoTitle                $title = null,
        private ?MetaDescription         $metaDescription = null,
        private ?SeoKeywords             $keywords = null,
        private ?WikiFontStyle           $fontStyle = null,
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

    public function themeColor(): ?HexColor
    {
        return $this->themeColor;
    }

    public function fontStyle(): ?WikiFontStyle
    {
        return $this->fontStyle;
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

    public function mergedAt(): DateTimeImmutable
    {
        return $this->mergedAt;
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
