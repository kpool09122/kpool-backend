<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Domain\ValueObject;

use DateTimeImmutable;
use Source\Wiki\Image\Domain\Exception\ImageDeletionRequestNotPendingException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class DeletionRequest
{
    public function __construct(
        private string $requesterName,
        private string $requesterEmail,
        private string $reason,
        private DateTimeImmutable $requestedAt,
        private ?PrincipalIdentifier $reviewerIdentifier,
        private ?DateTimeImmutable $reviewedAt,
        private ?string $rejectReason,
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

    public function rejectReason(): ?string
    {
        return $this->rejectReason;
    }

    public function approve(PrincipalIdentifier $reviewerIdentifier): self
    {
        if ($this->reviewedAt !== null) {
            throw new ImageDeletionRequestNotPendingException();
        }

        return new self(
            $this->requesterName,
            $this->requesterEmail,
            $this->reason,
            $this->requestedAt,
            $reviewerIdentifier,
            new DateTimeImmutable(),
            null,
        );
    }

    public function reject(PrincipalIdentifier $reviewerIdentifier, string $rejectReason): self
    {
        if ($this->reviewedAt !== null) {
            throw new ImageDeletionRequestNotPendingException();
        }

        return new self(
            $this->requesterName,
            $this->requesterEmail,
            $this->reason,
            $this->requestedAt,
            $reviewerIdentifier,
            new DateTimeImmutable(),
            $rejectReason,
        );
    }
}
