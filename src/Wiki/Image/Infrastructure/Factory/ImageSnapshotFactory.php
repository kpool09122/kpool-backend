<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Infrastructure\Factory;

use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Entity\ImageSnapshot;
use Source\Wiki\Image\Domain\Factory\ImageSnapshotFactoryInterface;
use Source\Wiki\Image\Domain\ValueObject\ImageSnapshotIdentifier;

readonly class ImageSnapshotFactory implements ImageSnapshotFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function create(
        Image          $image,
        TranslationSetIdentifier $translationSetIdentifier,
    ): ImageSnapshot {
        return new ImageSnapshot(
            new ImageSnapshotIdentifier($this->uuidGenerator->generate()),
            $image->imageIdentifier(),
            $translationSetIdentifier,
            $image->imagePath(),
            $image->imageUsage(),
            $image->displayOrder(),
            $image->sourceUrl(),
            $image->sourceName(),
            $image->altText(),
            $image->uploaderIdentifier(),
            $image->uploadedAt(),
            $image->approverIdentifier(),
            $image->approvedAt(),
            $image->updaterIdentifier(),
            $image->updatedAt(),
        );
    }
}
