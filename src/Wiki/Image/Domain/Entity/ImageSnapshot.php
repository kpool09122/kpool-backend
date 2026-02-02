<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Domain\ValueObject\ImageSnapshotIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;

readonly class ImageSnapshot
{
    public function __construct(
        private ImageSnapshotIdentifier $snapshotIdentifier,
        private ImageIdentifier         $imageIdentifier,
        private ResourceIdentifier      $resourceSnapshotIdentifier,
        private ImagePath               $imagePath,
        private ImageUsage              $imageUsage,
        private int                     $displayOrder,
        private string                  $sourceUrl,
        private string                  $sourceName,
        private string                  $altText,
        private PrincipalIdentifier     $uploaderIdentifier,
        private DateTimeImmutable       $uploadedAt,
        private ?PrincipalIdentifier    $approverIdentifier,
        private ?DateTimeImmutable      $approvedAt,
        private ?PrincipalIdentifier    $updaterIdentifier,
        private ?DateTimeImmutable      $updatedAt,
    ) {
    }

    public function snapshotIdentifier(): ImageSnapshotIdentifier
    {
        return $this->snapshotIdentifier;
    }

    public function imageIdentifier(): ImageIdentifier
    {
        return $this->imageIdentifier;
    }

    public function resourceSnapshotIdentifier(): ResourceIdentifier
    {
        return $this->resourceSnapshotIdentifier;
    }

    public function imagePath(): ImagePath
    {
        return $this->imagePath;
    }

    public function imageUsage(): ImageUsage
    {
        return $this->imageUsage;
    }

    public function displayOrder(): int
    {
        return $this->displayOrder;
    }

    public function uploaderIdentifier(): PrincipalIdentifier
    {
        return $this->uploaderIdentifier;
    }

    public function uploadedAt(): DateTimeImmutable
    {
        return $this->uploadedAt;
    }

    public function approverIdentifier(): ?PrincipalIdentifier
    {
        return $this->approverIdentifier;
    }

    public function approvedAt(): ?DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function updaterIdentifier(): ?PrincipalIdentifier
    {
        return $this->updaterIdentifier;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
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
}
