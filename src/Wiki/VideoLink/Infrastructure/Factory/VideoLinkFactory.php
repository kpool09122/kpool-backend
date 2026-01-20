<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Domain\Entity\VideoLink;
use Source\Wiki\VideoLink\Domain\Factory\VideoLinkFactoryInterface;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;

readonly class VideoLinkFactory implements VideoLinkFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function create(
        ResourceType $resourceType,
        ResourceIdentifier $resourceIdentifier,
        ExternalContentLink $url,
        VideoUsage $videoUsage,
        string $title,
        int $displayOrder,
    ): VideoLink {
        return new VideoLink(
            new VideoLinkIdentifier($this->uuidGenerator->generate()),
            $resourceType,
            $resourceIdentifier,
            $url,
            $videoUsage,
            $title,
            $displayOrder,
            new DateTimeImmutable(),
        );
    }
}
