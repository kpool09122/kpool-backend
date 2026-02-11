<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLinkAutoCollection\Application\UseCase\Command\CollectVideoLinks;

use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

class CollectVideoLinksOutput implements CollectVideoLinksOutputPort
{
    public bool $processed = false;

    public ?ResourceType $resourceType = null;

    public ?WikiIdentifier $wikiIdentifier = null;

    public int $collectedCount = 0;

    public ?string $message = null;

    public function noTargetResource(): void
    {
        $this->processed = false;
        $this->message = 'No target resource found for video link collection';
    }

    public function recentlyCollected(ResourceType $resourceType, WikiIdentifier $wikiIdentifier): void
    {
        $this->processed = false;
        $this->resourceType = $resourceType;
        $this->wikiIdentifier = $wikiIdentifier;
        $this->message = 'Resource was collected within the last month';
    }

    public function resourceNotFound(ResourceType $resourceType, WikiIdentifier $wikiIdentifier): void
    {
        $this->processed = false;
        $this->resourceType = $resourceType;
        $this->wikiIdentifier = $wikiIdentifier;
        $this->message = 'Resource not found';
    }

    public function success(ResourceType $resourceType, WikiIdentifier $wikiIdentifier, int $collectedCount): void
    {
        $this->processed = true;
        $this->resourceType = $resourceType;
        $this->wikiIdentifier = $wikiIdentifier;
        $this->collectedCount = $collectedCount;
        $this->message = "Successfully collected {$collectedCount} videos";
    }
}
