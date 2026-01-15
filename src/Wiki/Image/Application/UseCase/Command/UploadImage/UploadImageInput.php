<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\UploadImage;

use DateTimeImmutable;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class UploadImageInput implements UploadImageInputPort
{
    public function __construct(
        private PrincipalIdentifier $principalIdentifier,
        private ?ImageIdentifier $publishedImageIdentifier,
        private ResourceType $resourceType,
        private ResourceIdentifier $draftResourceIdentifier,
        private string $base64EncodedImage,
        private ImageUsage $imageUsage,
        private int $displayOrder,
        private string $sourceUrl,
        private string $sourceName,
        private string $altText,
        private DateTimeImmutable $agreedToTermsAt,
    ) {
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function publishedImageIdentifier(): ?ImageIdentifier
    {
        return $this->publishedImageIdentifier;
    }

    public function resourceType(): ResourceType
    {
        return $this->resourceType;
    }

    public function draftResourceIdentifier(): ResourceIdentifier
    {
        return $this->draftResourceIdentifier;
    }

    public function base64EncodedImage(): string
    {
        return $this->base64EncodedImage;
    }

    public function imageUsage(): ImageUsage
    {
        return $this->imageUsage;
    }

    public function displayOrder(): int
    {
        return $this->displayOrder;
    }

    public function sourceUrl(): string
    {
        return $this->sourceUrl;
    }

    public function sourceName(): string
    {
        return $this->sourceName;
    }

    public function altText(): string
    {
        return $this->altText;
    }

    public function agreedToTermsAt(): DateTimeImmutable
    {
        return $this->agreedToTermsAt;
    }
}
