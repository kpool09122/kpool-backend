<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query\SearchFreeMaterials;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

interface SearchFreeMaterialsInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    public function title(): ?string;

    public function displayName(): ?string;

    public function resourceType(): ResourceType;

    public function language(): Language;

    /** @return list<RelatedWikiInput> */
    public function relatedWikis(): array;
}
