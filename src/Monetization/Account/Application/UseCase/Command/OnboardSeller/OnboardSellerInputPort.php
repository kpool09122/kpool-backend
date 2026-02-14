<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\OnboardSeller;

use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Email;

interface OnboardSellerInputPort
{
    /**
     * @return MonetizationAccountIdentifier
     */
    public function monetizationAccountIdentifier(): MonetizationAccountIdentifier;

    /**
     * @return Email
     */
    public function email(): Email;

    /**
     * @return CountryCode
     */
    public function countryCode(): CountryCode;

    /**
     * @return string
     */
    public function refreshUrl(): string;

    /**
     * @return string
     */
    public function returnUrl(): string;
}
