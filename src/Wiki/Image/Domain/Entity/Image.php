<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class Image
{
    public function __construct(
        private readonly ImageIdentifier $imageIdentifier,
        private readonly ResourceType $resourceType,
        private readonly ResourceIdentifier $resourceIdentifier,
        private ImagePath $imagePath,
        private ImageUsage $imageUsage,
        private int $displayOrder,
        private string $sourceUrl,
        private string $sourceName,
        private string $altText,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
    }

    public function imageIdentifier(): ImageIdentifier
    {
        return $this->imageIdentifier;
    }

    public function resourceType(): ResourceType
    {
        return $this->resourceType;
    }

    public function resourceIdentifier(): ResourceIdentifier
    {
        return $this->resourceIdentifier;
    }

    public function imagePath(): ImagePath
    {
        return $this->imagePath;
    }

    public function setImagePath(ImagePath $imagePath): void
    {
        $this->imagePath = $imagePath;
    }

    public function imageUsage(): ImageUsage
    {
        return $this->imageUsage;
    }

    public function setImageUsage(ImageUsage $imageUsage): void
    {
        $this->imageUsage = $imageUsage;
    }

    public function displayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): void
    {
        $this->displayOrder = $displayOrder;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function sourceUrl(): string
    {
        return $this->sourceUrl;
    }

    public function setSourceUrl(string $sourceUrl): void
    {
        $this->sourceUrl = $sourceUrl;
    }

    public function sourceName(): string
    {
        return $this->sourceName;
    }

    public function setSourceName(string $sourceName): void
    {
        $this->sourceName = $sourceName;
    }

    public function altText(): string
    {
        return $this->altText;
    }

    public function setAltText(string $altText): void
    {
        $this->altText = $altText;
    }
}
