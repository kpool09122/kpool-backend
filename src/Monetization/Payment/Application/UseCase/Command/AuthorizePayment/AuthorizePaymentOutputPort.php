<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment;

use Source\Monetization\Payment\Domain\Entity\Payment;

interface AuthorizePaymentOutputPort
{
    public function setPayment(Payment $payment): void;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
