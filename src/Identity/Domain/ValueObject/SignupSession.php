<?php

declare(strict_types=1);

namespace Source\Identity\Domain\ValueObject;

use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\OneTimeToken;

readonly class SignupSession
{
    public function __construct(
        private ?AccountType $accountType = null,
        private ?OneTimeToken $oneTimeToken = null,
        private ?string $returnTo = null,
        private ?CountryCode $registrationCountry = null,
    ) {
    }

    public function registrationCountry(): ?CountryCode
    {
        return $this->registrationCountry;
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
