<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Factory;

use Source\Shared\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Domain\Entity\VideoLinkCollectionStatus;

interface VideoLinkCollectionStatusFactoryInterface
{
    public function create(
        ResourceType   $resourceType,
        WikiIdentifier $wikiIdentifier,
    ): VideoLinkCollectionStatus;
}
