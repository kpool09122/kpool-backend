<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\OnboardSeller;

use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Email;

interface OnboardSellerInputPort
{
    public function monetizationAccountIdentifier(): MonetizationAccountIdentifier;

    public function email(): Email;

    public function countryCode(): CountryCode;

    public function refreshUrl(): string;

    public function returnUrl(): string;
}
