<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Entity;

use DateTimeImmutable;
use Source\Account\Account\Domain\Exception\InvalidAccountTypeChangeRequestApprovalException;
use Source\Account\Account\Domain\Exception\InvalidAccountTypeChangeRequestRejectionException;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\AccountTypeChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountTypeChangeRequestStatus;
use Source\Account\Account\Domain\ValueObject\RejectionReason;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class AccountTypeChangeRequest
{
    public function __construct(
        private readonly AccountTypeChangeRequestIdentifier $requestIdentifier,
        private readonly AccountIdentifier $accountIdentifier,
        private readonly AccountType $currentAccountType,
        private readonly AccountType $requestedAccountType,
        private AccountTypeChangeRequestStatus $status,
        private readonly DateTimeImmutable $requestedAt,
        private ?AccountIdentifier $reviewedBy,
        private ?DateTimeImmutable $reviewedAt,
        private ?RejectionReason $rejectionReason,
    ) {
    }

    public function requestIdentifier(): AccountTypeChangeRequestIdentifier
    {
        return $this->requestIdentifier;
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function currentAccountType(): AccountType
    {
        return $this->currentAccountType;
    }

    public function requestedAccountType(): AccountType
    {
        return $this->requestedAccountType;
    }

    public function status(): AccountTypeChangeRequestStatus
    {
        return $this->status;
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

    public function approve(AccountIdentifier $reviewerAccountIdentifier): void
    {
        if (! $this->status->canTransitionTo(AccountTypeChangeRequestStatus::APPROVED)) {
            throw new InvalidAccountTypeChangeRequestApprovalException();
        }
        $this->status = AccountTypeChangeRequestStatus::APPROVED;
        $this->reviewedBy = $reviewerAccountIdentifier;
        $this->reviewedAt = new DateTimeImmutable();
        $this->rejectionReason = null;
    }

    public function reject(AccountIdentifier $reviewerAccountIdentifier, RejectionReason $rejectionReason): void
    {
        if (! $this->status->canTransitionTo(AccountTypeChangeRequestStatus::REJECTED)) {
            throw new InvalidAccountTypeChangeRequestRejectionException();
        }
        $this->status = AccountTypeChangeRequestStatus::REJECTED;
        $this->reviewedBy = $reviewerAccountIdentifier;
        $this->reviewedAt = new DateTimeImmutable();
        $this->rejectionReason = $rejectionReason;
    }
}
