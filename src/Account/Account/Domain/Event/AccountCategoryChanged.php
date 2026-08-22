<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Event;

use DateTimeImmutable;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class AccountCategoryChanged
{
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private AccountCategory $previousAccountCategory,
        private AccountCategory $newAccountCategory,
        private AccountIdentifier $reviewerAccountIdentifier,
        private DateTimeImmutable $changedAt,
        private AccountType $accountType,
    ) {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function previousAccountCategory(): AccountCategory
    {
        return $this->previousAccountCategory;
    }

    public function newAccountCategory(): AccountCategory
    {
        return $this->newAccountCategory;
    }

    public function reviewerAccountIdentifier(): AccountIdentifier
    {
        return $this->reviewerAccountIdentifier;
    }

    public function changedAt(): DateTimeImmutable
    {
        return $this->changedAt;
    }

    public function accountType(): AccountType
    {
        return $this->accountType;
    }
}
