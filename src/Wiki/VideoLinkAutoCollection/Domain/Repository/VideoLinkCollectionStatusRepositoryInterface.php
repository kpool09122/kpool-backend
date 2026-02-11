<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLinkAutoCollection\Domain\Repository;

use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLinkAutoCollection\Domain\Entity\VideoLinkCollectionStatus;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface VideoLinkCollectionStatusRepositoryInterface
{
    public function findByResource(ResourceType $resourceType, WikiIdentifier $wikiIdentifier): ?VideoLinkCollectionStatus;

    public function findNextTargetResource(): ?VideoLinkCollectionStatus;

    public function save(VideoLinkCollectionStatus $status): void;
}
