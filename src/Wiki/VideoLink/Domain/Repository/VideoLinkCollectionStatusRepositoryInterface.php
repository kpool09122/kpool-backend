<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Repository;

use Source\Shared\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Domain\Entity\VideoLinkCollectionStatus;

interface VideoLinkCollectionStatusRepositoryInterface
{
    public function findByResource(ResourceType $resourceType, WikiIdentifier $wikiIdentifier): ?VideoLinkCollectionStatus;

    public function findNextTargetResource(): ?VideoLinkCollectionStatus;

    public function save(VideoLinkCollectionStatus $status): void;
}
