<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use Source\Account\AccountVerification\Domain\ValueObject\ApplicantInfo;
use Source\Account\AccountVerification\Domain\ValueObject\RejectionReason;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationIdentifier;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationStatus;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class AccountVerification
{
    /**
     * @param VerificationDocument[] $documents
     */
    public function __construct(
        private readonly VerificationIdentifier $verificationIdentifier,
        private readonly AccountIdentifier $accountIdentifier,
        private readonly VerificationType $verificationType,
        private VerificationStatus $status,
        private readonly ApplicantInfo $applicantInfo,
        private readonly DateTimeImmutable $requestedAt,
        private ?AccountIdentifier $reviewedBy,
        private ?DateTimeImmutable $reviewedAt,
        private ?RejectionReason $rejectionReason,
        private array $documents = [],
    ) {
    }

    public function verificationIdentifier(): VerificationIdentifier
    {
        return $this->verificationIdentifier;
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function verificationType(): VerificationType
    {
        return $this->verificationType;
    }

    public function status(): VerificationStatus
    {
        return $this->status;
    }

    public function applicantInfo(): ApplicantInfo
    {
        return $this->applicantInfo;
    }

    public function requestedAt(): DateTimeImmutable
    {
        return $this->requestedAt;
    }

    public function reviewedBy(): ?AccountIdentifier
    {
        return $this->reviewedBy;
    }

    public function reviewedAt(): ?DateTimeImmutable
    {
        return $this->reviewedAt;
    }

    public function rejectionReason(): ?RejectionReason
    {
        return $this->rejectionReason;
    }

    /**
     * @return VerificationDocument[]
     */
    public function documents(): array
    {
        return $this->documents;
    }

    public function addDocument(VerificationDocument $document): void
    {
        $this->documents[] = $document;
    }

    public function approve(AccountIdentifier $reviewerAccountIdentifier): void
    {
        if (! $this->status->canTransitionTo(VerificationStatus::APPROVED)) {
            throw new DomainException('Cannot approve this verification.');
        }

        $this->status = VerificationStatus::APPROVED;
        $this->reviewedBy = $reviewerAccountIdentifier;
        $this->reviewedAt = new DateTimeImmutable();
        $this->rejectionReason = null;
    }

    public function reject(AccountIdentifier $reviewerAccountIdentifier, RejectionReason $rejectionReason): void
    {
        if (! $this->status->canTransitionTo(VerificationStatus::REJECTED)) {
            throw new DomainException('Cannot reject this verification.');
        }

        $this->status = VerificationStatus::REJECTED;
        $this->reviewedBy = $reviewerAccountIdentifier;
        $this->reviewedAt = new DateTimeImmutable();
        $this->rejectionReason = $rejectionReason;
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
