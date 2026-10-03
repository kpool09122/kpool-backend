<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Entity;

use DateTimeImmutable;
use Source\Account\Account\Domain\ValueObject\ArchivedAccountIdentifier;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class ArchivedAccount
{
    public function __construct(
        private ArchivedAccountIdentifier $archivedAccountIdentifier,
        private AccountIdentifier $accountIdentifier,
        private AccountCategory $accountCategory,
        private AccountType $accountType,
        private DateTimeImmutable $archivedAt,
    ) {
    }

    public function archivedAccountIdentifier(): ArchivedAccountIdentifier
    {
        return $this->archivedAccountIdentifier;
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function accountCategory(): AccountCategory
    {
        return $this->accountCategory;
    }

    public function accountType(): AccountType
    {
        return $this->accountType;
    }

    public function archivedAt(): DateTimeImmutable
    {
        return $this->archivedAt;
    }
}
