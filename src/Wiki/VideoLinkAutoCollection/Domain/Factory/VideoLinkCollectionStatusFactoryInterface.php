<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLinkAutoCollection\Domain\Factory;

use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLinkAutoCollection\Domain\Entity\VideoLinkCollectionStatus;

interface VideoLinkCollectionStatusFactoryInterface
{
    public function create(
        ResourceType $resourceType,
        ResourceIdentifier $resourceIdentifier,
    ): VideoLinkCollectionStatus;
}
