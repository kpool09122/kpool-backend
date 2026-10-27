<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\CreateVideoLink;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Wiki\Shared\Domain\ValueObject\DurationSeconds;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\ThumbnailUrl;
use Source\Wiki\Shared\Domain\ValueObject\TikTokVideoUrl;
use Source\Wiki\Shared\Domain\ValueObject\VideoTitle;
use Source\Wiki\Shared\Domain\ValueObject\YouTubeVideoUrl;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class CreateVideoLinkInput implements CreateVideoLinkInputPort
{
    public function __construct(
        private PrincipalIdentifier $principalIdentifier,
        private ResourceType $resourceType,
        private WikiIdentifier $wikiIdentifier,
        private YouTubeVideoUrl|TikTokVideoUrl $url,
        private VideoUsage $videoUsage,
        private VideoTitle $title,
        private ?DurationSeconds $durationSeconds,
        private ?ThumbnailUrl $thumbnailUrl = null,
        private ?DateTimeImmutable $publishedAt = null,
    ) {
        if ($url instanceof YouTubeVideoUrl && $durationSeconds === null) {
            throw new InvalidArgumentException('YouTube duration is required.');
        }
        if (! $videoUsage->isManual()) {
            throw new InvalidArgumentException('Invalid manual video input.');
        }
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function resourceType(): ResourceType
    {
        return $this->resourceType;
    }

    public function wikiIdentifier(): WikiIdentifier
    {
        return $this->wikiIdentifier;
    }

    public function url(): YouTubeVideoUrl|TikTokVideoUrl
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

    public function durationSeconds(): ?DurationSeconds
    {
        return $this->durationSeconds;
    }

    public function thumbnailUrl(): ?ThumbnailUrl
    {
        return $this->thumbnailUrl;
    }

    public function publishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }
}
