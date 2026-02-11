<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

class Image
{
    public function __construct(
        private readonly ImageIdentifier     $imageIdentifier,
        private readonly ResourceType        $resourceType,
        private readonly WikiIdentifier      $wikiIdentifier,
        private ImagePath                    $imagePath,
        private ImageUsage                   $imageUsage,
        private int                          $displayOrder,
        private string                       $sourceUrl,
        private string                       $sourceName,
        private string                       $altText,
        private bool                         $isHidden,
        private ?PrincipalIdentifier         $hiddenBy,
        private ?DateTimeImmutable           $hiddenAt,
        private readonly PrincipalIdentifier $uploaderIdentifier,
        private readonly DateTimeImmutable $uploadedAt,
        private ?PrincipalIdentifier $approverIdentifier,
        private ?DateTimeImmutable $approvedAt,
        private ?PrincipalIdentifier $updaterIdentifier,
        private ?DateTimeImmutable $updatedAt,
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

    public function wikiIdentifier(): WikiIdentifier
    {
        return $this->wikiIdentifier;
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

    public function setApproverIdentifier(PrincipalIdentifier $approverIdentifier): void
    {
        $this->approverIdentifier = $approverIdentifier;
    }

    public function approvedAt(): ?DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function setApprovedAt(DateTimeImmutable $approvedAt): void
    {
        $this->approvedAt = $approvedAt;
    }

    public function updaterIdentifier(): ?PrincipalIdentifier
    {
        return $this->updaterIdentifier;
    }

    public function setUpdaterIdentifier(PrincipalIdentifier $updaterIdentifier): void
    {
        $this->updaterIdentifier = $updaterIdentifier;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
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

    public function isHidden(): bool
    {
        return $this->isHidden;
    }

    public function hiddenBy(): ?PrincipalIdentifier
    {
        return $this->hiddenBy;
    }

    public function hiddenAt(): ?DateTimeImmutable
    {
        return $this->hiddenAt;
    }

    public function hide(PrincipalIdentifier $hiddenBy): void
    {
        $this->isHidden = true;
        $this->hiddenBy = $hiddenBy;
        $this->hiddenAt = new DateTimeImmutable();
    }

    public function unhide(): void
    {
        $this->isHidden = false;
        $this->hiddenBy = null;
        $this->hiddenAt = null;
    }
}
