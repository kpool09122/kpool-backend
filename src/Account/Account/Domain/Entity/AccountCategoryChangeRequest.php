<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Entity;

use DateTimeImmutable;
use Source\Account\Account\Domain\Exception\InvalidAccountCategoryChangeRequestApprovalException;
use Source\Account\Account\Domain\Exception\InvalidAccountCategoryChangeRequestRejectionException;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestStatus;
use Source\Account\Account\Domain\ValueObject\RejectionReason;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class AccountCategoryChangeRequest
{
    public function __construct(
        private readonly AccountCategoryChangeRequestIdentifier $requestIdentifier,
        private readonly AccountIdentifier $accountIdentifier,
        private readonly AccountCategory $currentAccountCategory,
        private readonly AccountCategory $requestedAccountCategory,
        private AccountCategoryChangeRequestStatus $status,
        private readonly DateTimeImmutable $requestedAt,
        private ?AccountIdentifier $reviewedBy,
        private ?DateTimeImmutable $reviewedAt,
        private ?RejectionReason $rejectionReason,
    ) {
    }

    public function requestIdentifier(): AccountCategoryChangeRequestIdentifier
    {
        return $this->requestIdentifier;
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function currentAccountCategory(): AccountCategory
    {
        return $this->currentAccountCategory;
    }

    public function requestedAccountCategory(): AccountCategory
    {
        return $this->requestedAccountCategory;
    }

    public function status(): AccountCategoryChangeRequestStatus
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
        if (! $this->status->canTransitionTo(AccountCategoryChangeRequestStatus::APPROVED)) {
            throw new InvalidAccountCategoryChangeRequestApprovalException();
        }
        $this->status = AccountCategoryChangeRequestStatus::APPROVED;
        $this->reviewedBy = $reviewerAccountIdentifier;
        $this->reviewedAt = new DateTimeImmutable();
        $this->rejectionReason = null;
    }

    public function reject(AccountIdentifier $reviewerAccountIdentifier, RejectionReason $rejectionReason): void
    {
        if (! $this->status->canTransitionTo(AccountCategoryChangeRequestStatus::REJECTED)) {
            throw new InvalidAccountCategoryChangeRequestRejectionException();
        }
        $this->status = AccountCategoryChangeRequestStatus::REJECTED;
        $this->reviewedBy = $reviewerAccountIdentifier;
        $this->reviewedAt = new DateTimeImmutable();
        $this->rejectionReason = $rejectionReason;
    }
}
