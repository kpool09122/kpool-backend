<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\OnboardSeller;

use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Email;

readonly class OnboardSellerInput implements OnboardSellerInputPort
{
    public function __construct(
        private MonetizationAccountIdentifier $monetizationAccountIdentifier,
        private Email $email,
        private CountryCode $countryCode,
        private string $refreshUrl,
        private string $returnUrl,
    ) {
    }

    public function monetizationAccountIdentifier(): MonetizationAccountIdentifier
    {
        return $this->monetizationAccountIdentifier;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function countryCode(): CountryCode
    {
        return $this->countryCode;
    }

    public function refreshUrl(): string
    {
        return $this->refreshUrl;
    }

    public function returnUrl(): string
    {
        return $this->returnUrl;
    }
}
