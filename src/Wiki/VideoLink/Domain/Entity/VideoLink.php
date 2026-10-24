<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Domain\ValueObject\DurationSeconds;
use Source\Wiki\VideoLink\Domain\ValueObject\ThumbnailUrl;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoTitle;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class VideoLink
{
    public function __construct(
        private VideoLinkIdentifier $videoLinkIdentifier,
        private ResourceType $resourceType,
        private WikiIdentifier $wikiIdentifier,
        private ExternalContentLink $url,
        private VideoUsage $videoUsage,
        private VideoTitle $title,
        private ?ThumbnailUrl $thumbnailUrl,
        private ?DateTimeImmutable $publishedAt,
        private DateTimeImmutable $createdAt,
        private ?DurationSeconds $durationSeconds,
        private ?PrincipalIdentifier $creatorPrincipalIdentifier = null,
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

    public function wikiIdentifier(): WikiIdentifier
    {
        return $this->wikiIdentifier;
    }

    public function url(): ExternalContentLink
    {
        return $this->url;
    }

    public function videoUsage(): VideoUsage
    {
        return $this->videoUsage;
    }

    public function title(): VideoTitle
    {
        return $this->title;
    }

    public function thumbnailUrl(): ?ThumbnailUrl
    {
        return $this->thumbnailUrl;
    }

    public function durationSeconds(): ?DurationSeconds
    {
        return $this->durationSeconds;
    }

    public function publishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function creatorPrincipalIdentifier(): ?PrincipalIdentifier
    {
        return $this->creatorPrincipalIdentifier;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
