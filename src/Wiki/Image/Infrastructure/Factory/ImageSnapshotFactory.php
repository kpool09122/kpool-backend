<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Entity\ImageSnapshot;
use Source\Wiki\Image\Domain\Factory\ImageSnapshotFactoryInterface;
use Source\Wiki\Image\Domain\ValueObject\ImageSnapshotIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;

readonly class ImageSnapshotFactory implements ImageSnapshotFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function create(
        Image $image,
        ResourceIdentifier $resourceSnapshotIdentifier,
    ): ImageSnapshot {
        return new ImageSnapshot(
            new ImageSnapshotIdentifier($this->uuidGenerator->generate()),
            $image->imageIdentifier(),
            $resourceSnapshotIdentifier,
            $image->imagePath(),
            $image->imageUsage(),
            $image->displayOrder(),
            new DateTimeImmutable(),
        );
    }
}
