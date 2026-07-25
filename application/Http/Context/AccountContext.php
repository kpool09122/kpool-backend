<?php

declare(strict_types=1);

namespace Application\Http\Context;

use Source\Account\Principal\Domain\Entity\Principal;

readonly class AccountContext
{
    /**
     * @param array<int, array<string, mixed>> $accountPolicies
     */
    public function __construct(
        private Principal $principal,
        private array $accountPolicies = [],
    ) {
    }

    public function principal(): Principal
    {
        return $this->principal;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function accountPolicies(): array
    {
        return $this->accountPolicies;
    }
}
