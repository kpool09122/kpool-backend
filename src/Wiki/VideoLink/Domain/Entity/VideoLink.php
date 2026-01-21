<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;

class VideoLink
{
    public function __construct(
        private readonly VideoLinkIdentifier $videoLinkIdentifier,
        private readonly ResourceType $resourceType,
        private readonly ResourceIdentifier $resourceIdentifier,
        private ExternalContentLink $url,
        private VideoUsage $videoUsage,
        private string $title,
        private ?string $thumbnailUrl,
        private ?DateTimeImmutable $publishedAt,
        private int $displayOrder,
        private readonly DateTimeImmutable $createdAt,
    ) {
    }

    public function videoLinkIdentifier(): VideoLinkIdentifier
    {
        return $this->videoLinkIdentifier;
    }

    public function resourceType(): ResourceType
    {
        return $this->resourceType;
    }

    public function resourceIdentifier(): ResourceIdentifier
    {
        return $this->resourceIdentifier;
    }

    public function url(): ExternalContentLink
    {
        return $this->url;
    }

    public function setUrl(ExternalContentLink $url): void
    {
        $this->url = $url;
    }

    public function videoUsage(): VideoUsage
    {
        return $this->videoUsage;
    }

    public function setVideoUsage(VideoUsage $videoUsage): void
    {
        $this->videoUsage = $videoUsage;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function thumbnailUrl(): ?string
    {
        return $this->thumbnailUrl;
    }

    public function setThumbnailUrl(?string $thumbnailUrl): void
    {
        $this->thumbnailUrl = $thumbnailUrl;
    }

    public function publishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?DateTimeImmutable $publishedAt): void
    {
        $this->publishedAt = $publishedAt;
    }

    public function displayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): void
    {
        $this->displayOrder = $displayOrder;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
