<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\CreateWiki;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Color;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class CreateWikiInput implements CreateWikiInputPort
{
    /**
     * @param WikiIdentifier|null $publishedWikiIdentifier
     * @param Language $language
     * @param ResourceType $resourceType
     * @param BasicInterface $basic
     * @param SectionContentCollection $sections
     * @param Color|null $themeColor
     * @param Slug $slug
     * @param PrincipalIdentifier $principalIdentifier
     * @param WikiIdentifier|null $agencyIdentifier
     * @param WikiIdentifier[] $groupIdentifiers
     * @param WikiIdentifier[] $talentIdentifiers
     */
    public function __construct(
        private ?WikiIdentifier          $publishedWikiIdentifier,
        private Language                 $language,
        private ResourceType             $resourceType,
        private BasicInterface           $basic,
        private SectionContentCollection $sections,
        private ?Color                   $themeColor,
        private Slug                     $slug,
        private PrincipalIdentifier      $principalIdentifier,
        private ?WikiIdentifier          $agencyIdentifier = null,
        private array                    $groupIdentifiers = [],
        private array                    $talentIdentifiers = [],
    ) {
    }

    public function publishedWikiIdentifier(): ?WikiIdentifier
    {
        return $this->publishedWikiIdentifier;
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function resourceType(): ResourceType
    {
        return $this->resourceType;
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

    public function slug(): Slug
    {
        return $this->slug;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
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
