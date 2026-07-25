<?php

declare(strict_types=1);

namespace Source\Identity\Domain\ValueObject;

use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\OneTimeToken;

readonly class SignupSession
{
    public function __construct(
        private ?AccountType $accountType = null,
        private ?OneTimeToken $oneTimeToken = null,
        private ?string $returnTo = null,
    ) {
    }

    public function accountType(): ?AccountType
    {
        return $this->accountType;
    }

    public function oneTimeToken(): ?OneTimeToken
    {
        return $this->oneTimeToken;
    }

    public function returnTo(): ?string
    {
        return $this->returnTo;
    }
}
