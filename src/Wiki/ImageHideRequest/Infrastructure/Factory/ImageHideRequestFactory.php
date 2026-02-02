<?php

declare(strict_types=1);

namespace Source\Wiki\ImageHideRequest\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Wiki\ImageHideRequest\Domain\Entity\ImageHideRequest;
use Source\Wiki\ImageHideRequest\Domain\Factory\ImageHideRequestFactoryInterface;
use Source\Wiki\ImageHideRequest\Domain\ValueObject\ImageHideRequestIdentifier;
use Source\Wiki\ImageHideRequest\Domain\ValueObject\ImageHideRequestStatus;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;

readonly class ImageHideRequestFactory implements ImageHideRequestFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function create(
        ImageIdentifier $imageIdentifier,
        string $requesterName,
        string $requesterEmail,
        string $reason,
    ): ImageHideRequest {
        return new ImageHideRequest(
            new ImageHideRequestIdentifier($this->uuidGenerator->generate()),
            $imageIdentifier,
            $requesterName,
            $requesterEmail,
            $reason,
            ImageHideRequestStatus::PENDING,
            new DateTimeImmutable(),
            null,
            null,
            null,
        );
    }
}
