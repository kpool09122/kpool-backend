<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Factory;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Shared\Domain\ValueObject\DurationSeconds;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\ThumbnailUrl;
use Source\Wiki\Shared\Domain\ValueObject\VideoTitle;
use Source\Wiki\VideoLink\Domain\Entity\VideoLink;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface VideoLinkFactoryInterface
{
    public function create(
        ResourceType $resourceType,
        WikiIdentifier $wikiIdentifier,
        ExternalContentLink $url,
        VideoUsage $videoUsage,
        VideoTitle $title,
        ?ThumbnailUrl $thumbnailUrl = null,
        ?DateTimeImmutable $publishedAt = null,
        ?DurationSeconds $durationSeconds = null,
        ?PrincipalIdentifier $creatorPrincipalIdentifier = null,
        ?ImagePath $thumbnailPath = null,
    ): VideoLink;
}
