<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\Factory;

use Source\Monetization\Account\Domain\Entity\PayoutAccount;
use Source\Monetization\Account\Domain\ValueObject\ExternalAccountId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;

interface PayoutAccountFactoryInterface
{
    public function create(
        MonetizationAccountIdentifier $monetizationAccountIdentifier,
        ExternalAccountId $externalAccountId,
    ): PayoutAccount;
}
