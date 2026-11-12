<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Factory;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\DurationSeconds;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\ThumbnailUrl;
use Source\Wiki\Shared\Domain\ValueObject\VideoTitle;
use Source\Wiki\VideoLink\Domain\Entity\VideoLink;

interface VideoLinkFactoryInterface
{
    public function create(
        ResourceType $resourceType,
        WikiIdentifier $wikiIdentifier,
        ExternalContentLink $url,
        VideoTitle $title,
        ?ThumbnailUrl $thumbnailUrl = null,
        ?DateTimeImmutable $publishedAt = null,
        ?DurationSeconds $durationSeconds = null,
        ?PrincipalIdentifier $creatorPrincipalIdentifier = null,
        ?ImagePath $thumbnailPath = null,
    ): VideoLink;
}
