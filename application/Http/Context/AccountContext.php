<?php

declare(strict_types=1);

namespace Application\Http\Context;

use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Principal\Domain\Entity\Principal;

readonly class AccountContext
{
    /**
     * @param array<int, array<string, mixed>> $accountPolicies
     */
    public function __construct(
        private Principal $principal,
        private AccountType $accountType,
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
}
