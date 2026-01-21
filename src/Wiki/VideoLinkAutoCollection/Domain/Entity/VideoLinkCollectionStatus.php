<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLinkAutoCollection\Domain\Entity;

use DateTimeImmutable;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLinkAutoCollection\Domain\ValueObject\VideoLinkCollectionStatusIdentifier;

class VideoLinkCollectionStatus
{
    public function __construct(
        private readonly VideoLinkCollectionStatusIdentifier $identifier,
        private readonly ResourceType $resourceType,
        private readonly ResourceIdentifier $resourceIdentifier,
        private ?DateTimeImmutable $lastCollectedAt,
        private readonly DateTimeImmutable $createdAt,
    ) {
    }

    public function identifier(): VideoLinkCollectionStatusIdentifier
    {
        return $this->identifier;
    }

    public function resourceType(): ResourceType
    {
        return $this->resourceType;
    }

    public function resourceIdentifier(): ResourceIdentifier
    {
        return $this->resourceIdentifier;
    }

    public function lastCollectedAt(): ?DateTimeImmutable
    {
        return $this->lastCollectedAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function markCollected(DateTimeImmutable $collectedAt): void
    {
        $this->lastCollectedAt = $collectedAt;
    }
}
