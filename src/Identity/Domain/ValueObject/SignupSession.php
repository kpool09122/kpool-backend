<?php

declare(strict_types=1);

namespace Source\Identity\Domain\ValueObject;

use Source\Account\Account\Domain\ValueObject\AccountType;

readonly class SignupSession
{
    public function __construct(
        private AccountType $accountType,
    ) {
    }

    public function accountType(): AccountType
    {
        return $this->accountType;
    }
}
