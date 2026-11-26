<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod;

use Source\Monetization\Account\Domain\Entity\PaymentMethod;

interface RegisterPaymentMethodOutputPort
{
    public function setPaymentMethod(PaymentMethod $paymentMethod): void;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
