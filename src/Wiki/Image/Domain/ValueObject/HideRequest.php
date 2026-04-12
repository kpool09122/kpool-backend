<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Domain\ValueObject;

use DateTimeImmutable;
use Source\Wiki\Image\Domain\Exception\ImageHideRequestNotPendingException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class HideRequest
{
    public function __construct(
        private string $requesterName,
        private string $requesterEmail,
        private string $reason,
        private ImageHideRequestStatus $status,
        private DateTimeImmutable $requestedAt,
        private ?PrincipalIdentifier $reviewerIdentifier,
        private ?DateTimeImmutable $reviewedAt,
        private ?string $reviewerComment,
    ) {
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

    public function approve(PrincipalIdentifier $reviewerIdentifier, string $reviewerComment): self
    {
        if (! $this->status->isPending()) {
            throw new ImageHideRequestNotPendingException();
        }

        return new self(
            $this->requesterName,
            $this->requesterEmail,
            $this->reason,
            ImageHideRequestStatus::APPROVED,
            $this->requestedAt,
            $reviewerIdentifier,
            new DateTimeImmutable(),
            $reviewerComment,
        );
    }

    public function reject(PrincipalIdentifier $reviewerIdentifier, string $reviewerComment): self
    {
        if (! $this->status->isPending()) {
            throw new ImageHideRequestNotPendingException();
        }

        return new self(
            $this->requesterName,
            $this->requesterEmail,
            $this->reason,
            ImageHideRequestStatus::REJECTED,
            $this->requestedAt,
            $reviewerIdentifier,
            new DateTimeImmutable(),
            $reviewerComment,
        );
    }
}
