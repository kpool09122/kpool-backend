<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment;

use Source\Monetization\Account\Domain\Entity\PaymentMethod;
use Source\Monetization\Payment\Domain\Entity\Payment;

interface AuthorizePaymentOutputPort
{
    public function setPayment(Payment $payment): void;

    public function setPaymentMethod(PaymentMethod $paymentMethod): void;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
