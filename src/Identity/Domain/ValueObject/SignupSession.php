<?php

declare(strict_types=1);

namespace Source\Identity\Domain\ValueObject;

use InvalidArgumentException;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\OneTimeToken;
use Source\Shared\Domain\ValueObject\SubdivisionCode;

readonly class SignupSession
{
    public function __construct(
        private ?AccountType $accountType = null,
        private ?OneTimeToken $oneTimeToken = null,
        private ?string $returnTo = null,
        private ?CountryCode $registrationCountry = null,
        private ?SubdivisionCode $registrationRegion = null,
    ) {
        if ($registrationRegion !== null && $registrationRegion->country() !== $registrationCountry) {
            throw new InvalidArgumentException('Registration region must belong to registration country.');
        }
    }

    public function registrationRegion(): ?SubdivisionCode
    {
        return $this->registrationRegion;
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
