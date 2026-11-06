<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\CollectVideoLinks;

use Source\Shared\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

interface CollectVideoLinksOutputPort
{
    public function noTargetResource(): void;

    public function resourceNotFound(ResourceType $resourceType, WikiIdentifier $wikiIdentifier): void;

    public function success(ResourceType $resourceType, WikiIdentifier $wikiIdentifier, int $collectedCount): void;
}
