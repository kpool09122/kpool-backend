<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Entity;

use DateTimeImmutable;
use Source\Account\Account\Domain\Exception\InvalidAccountSnapshotChangeRequestApprovalException;
use Source\Account\Account\Domain\Exception\InvalidAccountSnapshotChangeRequestRejectionException;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeReason;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeRequestStatus;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotIdentifier;
use Source\Account\Account\Domain\ValueObject\RejectionReason;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class AccountSnapshotChangeRequest
{
    public function __construct(
        private readonly AccountSnapshotChangeRequestIdentifier $requestIdentifier,
        private readonly AccountIdentifier $accountIdentifier,
        private readonly AccountSnapshotIdentifier $snapshotIdentifier,
        private readonly AccountSnapshotChangeReason $changeReason,
        private readonly DateTimeImmutable $requestedAt,
        private AccountSnapshotChangeRequestStatus $status,
        private ?AccountIdentifier $reviewedBy,
        private ?DateTimeImmutable $reviewedAt,
        private ?RejectionReason $rejectionReason,
        private ?AccountSnapshotIdentifier $reviewedSnapshotIdentifier,
    ) {
    }

    public function requestIdentifier(): AccountSnapshotChangeRequestIdentifier
    {
        return $this->requestIdentifier;
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function snapshotIdentifier(): AccountSnapshotIdentifier
    {
        return $this->snapshotIdentifier;
    }

    public function changeReason(): AccountSnapshotChangeReason
    {
        return $this->changeReason;
    }

    public function requestedAt(): DateTimeImmutable
    {
        return $this->requestedAt;
    }

    public function status(): AccountSnapshotChangeRequestStatus
    {
        return $this->status;
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

    public function reviewedSnapshotIdentifier(): ?AccountSnapshotIdentifier
    {
        return $this->reviewedSnapshotIdentifier;
    }

    /** @throws InvalidAccountSnapshotChangeRequestApprovalException */
    public function approve(AccountIdentifier $reviewer, AccountSnapshotIdentifier $reviewedSnapshotIdentifier): void
    {
        if ($this->status !== AccountSnapshotChangeRequestStatus::PENDING) {
            throw new InvalidAccountSnapshotChangeRequestApprovalException();
        }
        $this->status = AccountSnapshotChangeRequestStatus::APPROVED;
        $this->reviewedBy = $reviewer;
        $this->reviewedAt = new DateTimeImmutable();
        $this->reviewedSnapshotIdentifier = $reviewedSnapshotIdentifier;
    }

    /** @throws InvalidAccountSnapshotChangeRequestRejectionException */
    public function reject(AccountIdentifier $reviewer, RejectionReason $reason, AccountSnapshotIdentifier $reviewedSnapshotIdentifier): void
    {
        if ($this->status !== AccountSnapshotChangeRequestStatus::PENDING) {
            throw new InvalidAccountSnapshotChangeRequestRejectionException();
        }
        $this->status = AccountSnapshotChangeRequestStatus::REJECTED;
        $this->reviewedBy = $reviewer;
        $this->reviewedAt = new DateTimeImmutable();
        $this->rejectionReason = $reason;
        $this->reviewedSnapshotIdentifier = $reviewedSnapshotIdentifier;
    }
}
