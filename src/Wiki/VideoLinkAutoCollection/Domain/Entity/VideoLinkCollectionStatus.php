<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLinkAutoCollection\Domain\Entity;

use DateTimeImmutable;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLinkAutoCollection\Domain\ValueObject\VideoLinkCollectionStatusIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

class VideoLinkCollectionStatus
{
    public function __construct(
        private readonly VideoLinkCollectionStatusIdentifier $identifier,
        private readonly ResourceType                        $resourceType,
        private readonly WikiIdentifier                      $wikiIdentifier,
        private ?DateTimeImmutable                           $lastCollectedAt,
        private readonly DateTimeImmutable                   $createdAt,
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

    public function wikiIdentifier(): WikiIdentifier
    {
        return $this->wikiIdentifier;
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
