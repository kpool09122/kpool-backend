<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Domain\Entity\VideoLinkCollectionStatus;
use Source\Wiki\VideoLink\Domain\Factory\VideoLinkCollectionStatusFactoryInterface;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkCollectionStatusIdentifier;

readonly class VideoLinkCollectionStatusFactory implements VideoLinkCollectionStatusFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function create(
        ResourceType   $resourceType,
        WikiIdentifier $wikiIdentifier,
    ): VideoLinkCollectionStatus {
        return new VideoLinkCollectionStatus(
            new VideoLinkCollectionStatusIdentifier($this->uuidGenerator->generate()),
            $resourceType,
            $wikiIdentifier,
            null,
            new DateTimeImmutable(),
        );
    }
}
