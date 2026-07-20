<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Image\Domain\Exception\ImageDeletionRequestAlreadyPendingException;
use Source\Wiki\Image\Domain\Exception\ImageDeletionRequestNotPendingException;
use Source\Wiki\Image\Domain\ValueObject\DeletionRequest;
use Source\Wiki\Image\Domain\ValueObject\RightsConfirmationAgreed;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class Image
{
    /**
     * @param DeletionRequest[] $deletionRequests
     */
    public function __construct(
        private readonly ImageIdentifier     $imageIdentifier,
        private readonly ResourceType        $resourceType,
        private readonly TranslationSetIdentifier      $translationSetIdentifier,
        private ImagePath                    $imagePath,
        private int                          $displayOrder,
        private string                       $sourceUrl,
        private string                       $sourceName,
        private string                       $altText,
        private bool                         $isHidden,
        private ?DateTimeImmutable           $hiddenAt,
        private readonly PrincipalIdentifier $uploaderIdentifier,
        private readonly DateTimeImmutable $uploadedAt,
        private ?PrincipalIdentifier $approverIdentifier,
        private ?DateTimeImmutable $approvedAt,
        private ?PrincipalIdentifier $updaterIdentifier,
        private ?DateTimeImmutable $updatedAt,
        private RightsConfirmationAgreed $rightsConfirmationAgreed,
        private array $deletionRequests = [],
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

    public function translationSetIdentifier(): TranslationSetIdentifier
    {
        return $this->translationSetIdentifier;
    }

    public function imagePath(): ImagePath
    {
        return $this->imagePath;
    }

    public function setImagePath(ImagePath $imagePath): void
    {
        $this->imagePath = $imagePath;
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

    public function rightsConfirmationAgreed(): RightsConfirmationAgreed
    {
        return $this->rightsConfirmationAgreed;
    }

    public function setRightsConfirmationAgreed(RightsConfirmationAgreed $rightsConfirmationAgreed): void
    {
        $this->rightsConfirmationAgreed = $rightsConfirmationAgreed;
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

    public function hiddenAt(): ?DateTimeImmutable
    {
        return $this->hiddenAt;
    }

    public function hide(): void
    {
        $this->isHidden = true;
        $this->hiddenAt = new DateTimeImmutable();
    }

    public function unhide(): void
    {
        $this->isHidden = false;
        $this->hiddenAt = null;
    }

    /**
     * @return DeletionRequest[]
     */
    public function deletionRequests(): array
    {
        return $this->deletionRequests;
    }

    public function pendingDeletionRequest(): ?DeletionRequest
    {
        foreach ($this->deletionRequests as $deletionRequest) {
            if ($deletionRequest->reviewedAt() === null) {
                return $deletionRequest;
            }
        }

        return null;
    }

    public function latestDeletionRequest(): ?DeletionRequest
    {
        if ($this->deletionRequests === []) {
            return null;
        }

        return $this->deletionRequests[array_key_last($this->deletionRequests)];
    }

    public function requestDeletion(string $requesterName, string $requesterEmail, string $reason): void
    {
        if ($this->pendingDeletionRequest() !== null) {
            throw new ImageDeletionRequestAlreadyPendingException();
        }

        $this->deletionRequests[] = new DeletionRequest(
            $requesterName,
            $requesterEmail,
            $reason,
            new DateTimeImmutable(),
            null,
            null,
            null,
        );

        $this->isHidden = true;
        $this->hiddenAt = new DateTimeImmutable();
    }

    public function approveDeletionRequest(PrincipalIdentifier $reviewerIdentifier): void
    {
        $pendingDeletionRequest = $this->pendingDeletionRequest();
        if ($pendingDeletionRequest === null) {
            throw new ImageDeletionRequestNotPendingException();
        }

        foreach ($this->deletionRequests as $index => $deletionRequest) {
            if ($deletionRequest === $pendingDeletionRequest) {
                $this->deletionRequests[$index] = $deletionRequest->approve($reviewerIdentifier);

                break;
            }
        }

    }

    public function rejectDeletionRequest(PrincipalIdentifier $reviewerIdentifier, string $rejectReason): void
    {
        $pendingDeletionRequest = $this->pendingDeletionRequest();
        if ($pendingDeletionRequest === null) {
            throw new ImageDeletionRequestNotPendingException();
        }

        foreach ($this->deletionRequests as $index => $deletionRequest) {
            if ($deletionRequest === $pendingDeletionRequest) {
                $this->deletionRequests[$index] = $deletionRequest->reject($reviewerIdentifier, $rejectReason);

                break;
            }
        }

        $this->unhide();
    }
}
