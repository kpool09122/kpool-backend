<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\DurationSeconds;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\ThumbnailUrl;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoTitle;

readonly class VideoLink
{
    public function __construct(
        private VideoLinkIdentifier $videoLinkIdentifier,
        private ResourceType $resourceType,
        private WikiIdentifier $wikiIdentifier,
        private ExternalContentLink $url,
        private VideoTitle $title,
        private ?ThumbnailUrl $thumbnailUrl,
        private ?DateTimeImmutable $publishedAt,
        private DateTimeImmutable $createdAt,
        private ?DurationSeconds $durationSeconds,
        private ?PrincipalIdentifier $creatorPrincipalIdentifier = null,
        private ?ImagePath $thumbnailPath = null,
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

    public function thumbnailPath(): ?ImagePath
    {
        return $this->thumbnailPath;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
