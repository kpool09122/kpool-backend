<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Factory\ImageFactoryInterface;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class ImageFactory implements ImageFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function create(
        ResourceType $resourceType,
        ResourceIdentifier $resourceIdentifier,
        ImagePath $imagePath,
        ImageUsage $imageUsage,
        int $displayOrder,
        string $sourceUrl,
        string $sourceName,
        string $altText,
    ): Image {
        $now = new DateTimeImmutable();

        return new Image(
            new ImageIdentifier($this->uuidGenerator->generate()),
            $resourceType,
            $resourceIdentifier,
            $imagePath,
            $imageUsage,
            $displayOrder,
            $sourceUrl,
            $sourceName,
            $altText,
            $now,
            $now,
        );
    }
}
