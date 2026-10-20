<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query\SearchFreeMaterials;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

final readonly class SearchFreeMaterialsInput implements SearchFreeMaterialsInputPort
{
    /** @param list<RelatedWikiInput> $relatedWikis */
    public function __construct(
        private PrincipalIdentifier $principalIdentifier,
        private ?string $title,
        private ?string $displayName,
        private ResourceType $resourceType,
        private Language $language,
        private array $relatedWikis,
    ) {
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function title(): ?string
    {
        return $this->title;
    }

    public function displayName(): ?string
    {
        return $this->displayName;
    }

    public function resourceType(): ResourceType
    {
        return $this->resourceType;
    }

    public function language(): Language
    {
        return $this->language;
    }

    /** @return list<RelatedWikiInput> */
    public function relatedWikis(): array
    {
        return $this->relatedWikis;
    }
}
