<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\DurationSeconds;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\ThumbnailUrl;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoTitle;
use Source\Wiki\VideoLink\Domain\Entity\VideoLink;
use Source\Wiki\VideoLink\Domain\Factory\VideoLinkFactoryInterface;

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
        VideoTitle $title,
        ?ThumbnailUrl $thumbnailUrl = null,
        ?DateTimeImmutable $publishedAt = null,
        ?DurationSeconds $durationSeconds = null,
        ?PrincipalIdentifier $creatorPrincipalIdentifier = null,
        ?ImagePath $thumbnailPath = null,
    ): VideoLink {
        return new VideoLink(
            new VideoLinkIdentifier($this->uuidGenerator->generate()),
            $resourceType,
            $wikiIdentifier,
            $url,
            $title,
            $thumbnailUrl,
            $publishedAt,
            new DateTimeImmutable(),
            $durationSeconds,
            $creatorPrincipalIdentifier,
            $thumbnailPath,
        );
    }
}
