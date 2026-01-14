<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageSnapshotIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;

class ImageSnapshot
{
    public function __construct(
        private readonly ImageSnapshotIdentifier $snapshotIdentifier,
        private readonly ImageIdentifier $imageIdentifier,
        private readonly ResourceIdentifier $resourceSnapshotIdentifier,
        private readonly ImagePath $imagePath,
        private readonly ImageUsage $imageUsage,
        private readonly int $displayOrder,
        private readonly DateTimeImmutable $createdAt,
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

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
