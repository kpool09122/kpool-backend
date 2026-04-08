<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod;

use Source\Monetization\Account\Domain\Entity\RegisteredPaymentMethod;

class RegisterPaymentMethodOutput implements RegisterPaymentMethodOutputPort
{
    private ?RegisteredPaymentMethod $paymentMethod = null;

    private bool $skipped = false;

    public function setRegisteredPaymentMethod(RegisteredPaymentMethod $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function setSkipped(bool $skipped): void
    {
        $this->skipped = $skipped;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->paymentMethod === null) {
            return [
                'registeredPaymentMethodIdentifier' => null,
                'skipped' => $this->skipped,
            ];
        }

        $meta = $this->paymentMethod->meta();

        return [
            'registeredPaymentMethodIdentifier' => (string) $this->paymentMethod->paymentMethodIdentifier(),
            'paymentMethodId' => (string) $this->paymentMethod->paymentMethodId(),
            'type' => $this->paymentMethod->type()->value,
            'brand' => $meta?->brand(),
            'last4' => $meta?->last4(),
            'expMonth' => $meta?->expMonth(),
            'expYear' => $meta?->expYear(),
            'isDefault' => $this->paymentMethod->isDefault(),
            'skipped' => $this->skipped,
        ];
    }
}
