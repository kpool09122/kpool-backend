<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\Service;

use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface VideoLinkCollectionRegistrationServiceInterface
{
    public function register(ResourceType $resourceType, WikiIdentifier $wikiIdentifier): void;
}
