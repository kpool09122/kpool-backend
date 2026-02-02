<?php

declare(strict_types=1);

namespace Source\Wiki\ImageHideRequest\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use Source\Wiki\ImageHideRequest\Domain\ValueObject\ImageHideRequestIdentifier;
use Source\Wiki\ImageHideRequest\Domain\ValueObject\ImageHideRequestStatus;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class ImageHideRequest
{
    public function __construct(
        private readonly ImageHideRequestIdentifier $requestIdentifier,
        private readonly ImageIdentifier $imageIdentifier,
        private readonly string $requesterName,
        private readonly string $requesterEmail,
        private readonly string $reason,
        private ImageHideRequestStatus $status,
        private readonly DateTimeImmutable $requestedAt,
        private ?PrincipalIdentifier $reviewerIdentifier,
        private ?DateTimeImmutable $reviewedAt,
        private ?string $reviewerComment,
    ) {
    }

    public function requestIdentifier(): ImageHideRequestIdentifier
    {
        return $this->requestIdentifier;
    }

    public function imageIdentifier(): ImageIdentifier
    {
        return $this->imageIdentifier;
    }

    public function requesterName(): string
    {
        return $this->requesterName;
    }

    public function requesterEmail(): string
    {
        return $this->requesterEmail;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function status(): ImageHideRequestStatus
    {
        return $this->status;
    }

    public function requestedAt(): DateTimeImmutable
    {
        return $this->requestedAt;
    }

    public function reviewerIdentifier(): ?PrincipalIdentifier
    {
        return $this->reviewerIdentifier;
    }

    public function reviewedAt(): ?DateTimeImmutable
    {
        return $this->reviewedAt;
    }

    public function reviewerComment(): ?string
    {
        return $this->reviewerComment;
    }

    public function approve(PrincipalIdentifier $reviewerIdentifier, string $reviewerComment): void
    {
        if (! $this->status->isPending()) {
            throw new DomainException('Only pending requests can be approved.');
        }

        $this->status = ImageHideRequestStatus::APPROVED;
        $this->reviewerIdentifier = $reviewerIdentifier;
        $this->reviewedAt = new DateTimeImmutable();
        $this->reviewerComment = $reviewerComment;
    }

    public function reject(PrincipalIdentifier $reviewerIdentifier, string $reviewerComment): void
    {
        if (! $this->status->isPending()) {
            throw new DomainException('Only pending requests can be rejected.');
        }

        $this->status = ImageHideRequestStatus::REJECTED;
        $this->reviewerIdentifier = $reviewerIdentifier;
        $this->reviewedAt = new DateTimeImmutable();
        $this->reviewerComment = $reviewerComment;
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function isApproved(): bool
    {
        return $this->status->isApproved();
    }

    public function isRejected(): bool
    {
        return $this->status->isRejected();
    }
}
