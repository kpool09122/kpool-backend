<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Domain\Entity\VideoLink;
use Source\Wiki\VideoLink\Domain\Factory\VideoLinkFactoryInterface;
use Source\Wiki\VideoLink\Domain\ValueObject\DurationSeconds;
use Source\Wiki\VideoLink\Domain\ValueObject\ThumbnailUrl;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoTitle;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class VideoLinkFactory implements VideoLinkFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

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
    ): VideoLink {
        return new VideoLink(
            new VideoLinkIdentifier($this->uuidGenerator->generate()),
            $resourceType,
            $wikiIdentifier,
            $url,
            $videoUsage,
            $title,
            $thumbnailUrl,
            $publishedAt,
            new DateTimeImmutable(),
            $durationSeconds,
            $creatorPrincipalIdentifier,
        );
    }
}
