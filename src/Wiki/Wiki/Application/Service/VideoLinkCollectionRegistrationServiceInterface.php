<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\Service;

use Source\Shared\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

interface VideoLinkCollectionRegistrationServiceInterface
{
    public function register(ResourceType $resourceType, WikiIdentifier $wikiIdentifier): void;
}
