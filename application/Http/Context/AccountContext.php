<?php

declare(strict_types=1);

namespace Application\Http\Context;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountCategory;

readonly class AccountContext
{
    /**
     * @param array<int, array<string, mixed>> $accountPolicies
     */
    public function __construct(
        private Principal $principal,
        private AccountType $accountType,
        private AccountCategory $accountCategory,
        private array $accountPolicies = [],
    ) {
    }

    public function principal(): Principal
    {
        return $this->principal;
    }

    public function accountType(): AccountType
    {
        return $this->accountType;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function accountPolicies(): array
    {
        return $this->accountPolicies;
    }

    public function accountCategory(): AccountCategory
    {
        return $this->accountCategory;
    }
}
