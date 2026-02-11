<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLinkAutoCollection\Application\UseCase\Command\CollectVideoLinks;

use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface CollectVideoLinksOutputPort
{
    public function noTargetResource(): void;

    public function recentlyCollected(ResourceType $resourceType, WikiIdentifier $wikiIdentifier): void;

    public function resourceNotFound(ResourceType $resourceType, WikiIdentifier $wikiIdentifier): void;

    public function success(ResourceType $resourceType, WikiIdentifier $wikiIdentifier, int $collectedCount): void;
}
