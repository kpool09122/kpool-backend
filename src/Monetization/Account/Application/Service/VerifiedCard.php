<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\Service;

use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodMeta;

readonly class VerifiedCard
{
    public function __construct(public ExternalPaymentMethodId $externalPaymentMethodId, public PaymentMethodMeta $meta)
    {
    }
}
