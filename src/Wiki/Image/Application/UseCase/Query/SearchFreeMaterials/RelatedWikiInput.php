<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query\SearchFreeMaterials;

use Source\Shared\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

final readonly class RelatedWikiInput
{
    public function __construct(public WikiIdentifier $identifier, public string $title, public ResourceType $resourceType, public string $relationType)
    {
    }
}
