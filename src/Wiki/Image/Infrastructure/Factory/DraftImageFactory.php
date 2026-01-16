<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Domain\Entity\DraftImage;
use Source\Wiki\Image\Domain\Factory\DraftImageFactoryInterface;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class DraftImageFactory implements DraftImageFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function create(
        ?ImageIdentifier $publishedImageIdentifier,
        ResourceType $resourceType,
        ResourceIdentifier $draftResourceIdentifier,
        PrincipalIdentifier $editorIdentifier,
        ImagePath $imagePath,
        ImageUsage $imageUsage,
        int $displayOrder,
        string $sourceUrl,
        string $sourceName,
        string $altText,
        DateTimeImmutable $agreedToTermsAt,
    ): DraftImage {
        return new DraftImage(
            new ImageIdentifier($this->uuidGenerator->generate()),
            $publishedImageIdentifier,
            $resourceType,
            $draftResourceIdentifier,
            $editorIdentifier,
            $imagePath,
            $imageUsage,
            $displayOrder,
            $sourceUrl,
            $sourceName,
            $altText,
            ApprovalStatus::UnderReview,
            $agreedToTermsAt,
            new DateTimeImmutable(),
        );
    }
}
