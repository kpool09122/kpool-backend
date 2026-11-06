<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\CreateVideoLink;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\DurationSeconds;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\ThumbnailUrl;
use Source\Wiki\Shared\Domain\ValueObject\TikTokVideoUrl;
use Source\Wiki\Shared\Domain\ValueObject\VideoTitle;
use Source\Wiki\Shared\Domain\ValueObject\YouTubeVideoUrl;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;

interface CreateVideoLinkInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    public function resourceType(): ResourceType;

    public function wikiIdentifier(): WikiIdentifier;

    public function url(): YouTubeVideoUrl|TikTokVideoUrl;

    public function videoUsage(): VideoUsage;

    public function title(): VideoTitle;

    public function durationSeconds(): ?DurationSeconds;

    public function thumbnailUrl(): ?ThumbnailUrl;

    public function publishedAt(): ?DateTimeImmutable;
}
