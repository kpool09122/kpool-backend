<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\Service;

use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodMeta;

interface PaymentMethodMetaResolverInterface
{
    public function resolve(ExternalPaymentMethodId $externalPaymentMethodId): ?PaymentMethodMeta;
}
