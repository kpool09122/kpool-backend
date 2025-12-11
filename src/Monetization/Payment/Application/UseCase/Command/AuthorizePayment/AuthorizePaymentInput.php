<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment;

use Source\Monetization\Payment\Domain\ValueObject\PaymentMethod;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;

readonly class AuthorizePaymentInput implements AuthorizePaymentInputPort
{
    public function __construct(
        private OrderIdentifier $orderIdentifier,
        private Money $money,
        private PaymentMethod $paymentMethod,
    ) {
    }

    public function orderIdentifier(): OrderIdentifier
    {
        return $this->orderIdentifier;
    }

    public function money(): Money
    {
        return $this->money;
    }

    public function paymentMethod(): PaymentMethod
    {
        return $this->paymentMethod;
    }
}
