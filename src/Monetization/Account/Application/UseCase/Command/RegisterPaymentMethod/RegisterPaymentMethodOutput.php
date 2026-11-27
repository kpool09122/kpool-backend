<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod;

use Source\Monetization\Account\Domain\Entity\PaymentMethod;

class RegisterPaymentMethodOutput implements RegisterPaymentMethodOutputPort
{
    private ?PaymentMethod $paymentMethod = null;

    public function setPaymentMethod(PaymentMethod $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->paymentMethod === null) {
            return [
                'paymentMethodIdentifier' => null,
            ];
        }

        $meta = $this->paymentMethod->meta();

        return [
            'paymentMethodIdentifier' => (string) $this->paymentMethod->paymentMethodIdentifier(),
            'type' => $this->paymentMethod->type()->value,
            'brand' => $meta?->brand(),
            'last4' => $meta?->last4(),
            'expMonth' => $meta?->expMonth(),
            'expYear' => $meta?->expYear(),
            'isDefault' => $this->paymentMethod->isDefault(),
            'status' => $this->paymentMethod->status()->value,
            'archivedAt' => null,
        ];
    }
}
