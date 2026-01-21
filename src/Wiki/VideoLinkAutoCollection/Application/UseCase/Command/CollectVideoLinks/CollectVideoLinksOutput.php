<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLinkAutoCollection\Application\UseCase\Command\CollectVideoLinks;

use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class CollectVideoLinksOutput implements CollectVideoLinksOutputPort
{
    public bool $processed = false;

    public ?ResourceType $resourceType = null;

    public ?ResourceIdentifier $resourceIdentifier = null;

    public int $collectedCount = 0;

    public ?string $message = null;

    public function noTargetResource(): void
    {
        $this->processed = false;
        $this->message = 'No target resource found for video link collection';
    }

    public function resourceNotFound(ResourceType $resourceType, ResourceIdentifier $resourceIdentifier): void
    {
        $this->processed = false;
        $this->resourceType = $resourceType;
        $this->resourceIdentifier = $resourceIdentifier;
        $this->message = 'Resource not found';
    }

    public function success(ResourceType $resourceType, ResourceIdentifier $resourceIdentifier, int $collectedCount): void
    {
        $this->processed = true;
        $this->resourceType = $resourceType;
        $this->resourceIdentifier = $resourceIdentifier;
        $this->collectedCount = $collectedCount;
        $this->message = "Successfully collected {$collectedCount} videos";
    }
}
