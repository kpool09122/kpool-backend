<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLinkAutoCollection\Application\UseCase\Command\CollectVideoLinks;

use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

interface CollectVideoLinksOutputPort
{
    public function noTargetResource(): void;

    public function recentlyCollected(ResourceType $resourceType, ResourceIdentifier $resourceIdentifier): void;

    public function resourceNotFound(ResourceType $resourceType, ResourceIdentifier $resourceIdentifier): void;

    public function success(ResourceType $resourceType, ResourceIdentifier $resourceIdentifier, int $collectedCount): void;
}
