<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Factory;

use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Domain\Entity\VideoLinkCollectionStatus;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface VideoLinkCollectionStatusFactoryInterface
{
    public function create(
        ResourceType   $resourceType,
        WikiIdentifier $wikiIdentifier,
    ): VideoLinkCollectionStatus;
}
