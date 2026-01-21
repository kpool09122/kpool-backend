<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLinkAutoCollection\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLinkAutoCollection\Domain\Entity\VideoLinkCollectionStatus;
use Source\Wiki\VideoLinkAutoCollection\Domain\Factory\VideoLinkCollectionStatusFactoryInterface;
use Source\Wiki\VideoLinkAutoCollection\Domain\ValueObject\VideoLinkCollectionStatusIdentifier;

readonly class VideoLinkCollectionStatusFactory implements VideoLinkCollectionStatusFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function create(
        ResourceType $resourceType,
        ResourceIdentifier $resourceIdentifier,
    ): VideoLinkCollectionStatus {
        return new VideoLinkCollectionStatus(
            new VideoLinkCollectionStatusIdentifier($this->uuidGenerator->generate()),
            $resourceType,
            $resourceIdentifier,
            null,
            new DateTimeImmutable(),
        );
    }
}
