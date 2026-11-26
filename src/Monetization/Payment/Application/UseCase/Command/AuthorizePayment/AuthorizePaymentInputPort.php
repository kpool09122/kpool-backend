<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment;

use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;

interface AuthorizePaymentInputPort
{
    public function orderIdentifier(): OrderIdentifier;

    public function buyerMonetizationAccountIdentifier(): MonetizationAccountIdentifier;

    public function money(): Money;

    public function paymentMethodIdentifier(): PaymentMethodIdentifier;
}
