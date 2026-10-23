<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Repository;

use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Domain\Entity\VideoLinkCollectionStatus;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface VideoLinkCollectionStatusRepositoryInterface
{
    public function findByResource(ResourceType $resourceType, WikiIdentifier $wikiIdentifier): ?VideoLinkCollectionStatus;

    public function findNextTargetResource(): ?VideoLinkCollectionStatus;

    public function save(VideoLinkCollectionStatus $status): void;
}
