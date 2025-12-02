<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Domain\ValueObject;

use InvalidArgumentException;

readonly class PaymentMethod
{
    public function __construct(
        private PaymentMethodIdentifier $paymentMethodIdentifier,
        private PaymentMethodType       $type,
        private string                  $label,
        private bool                    $recurringEnabled,
    ) {
        $this->assertLabel($label);
    }

    public function paymentMethodIdentifier(): PaymentMethodIdentifier
    {
        return $this->paymentMethodIdentifier;
    }

    public function type(): PaymentMethodType
    {
        return $this->type;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function isRecurringEnabled(): bool
    {
        return $this->recurringEnabled;
    }

    private function assertLabel(string $label): void
    {
        if (trim($label) === '') {
            throw new InvalidArgumentException('Payment method label must not be empty.');
        }
    }
}
