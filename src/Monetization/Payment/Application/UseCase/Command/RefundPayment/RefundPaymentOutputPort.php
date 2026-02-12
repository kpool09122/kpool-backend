<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Application\UseCase\Command\RefundPayment;

use Source\Monetization\Payment\Domain\Entity\Payment;

interface RefundPaymentOutputPort
{
    public function setPayment(Payment $payment): void;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
