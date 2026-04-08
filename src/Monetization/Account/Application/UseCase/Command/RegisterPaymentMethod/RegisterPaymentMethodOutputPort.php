<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod;

use Source\Monetization\Account\Domain\Entity\RegisteredPaymentMethod;

interface RegisterPaymentMethodOutputPort
{
    public function setRegisteredPaymentMethod(RegisteredPaymentMethod $paymentMethod): void;

    public function setSkipped(bool $skipped): void;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
