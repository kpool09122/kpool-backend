<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\Entity;

use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodMeta;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodStatus;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Account\Domain\ValueObject\RegisteredPaymentMethodIdentifier;

class RegisteredPaymentMethod
{
    public function __construct(
        private readonly RegisteredPaymentMethodIdentifier $paymentMethodIdentifier,
        private readonly MonetizationAccountIdentifier     $monetizationAccountIdentifier,
        private readonly PaymentMethodId                   $paymentMethodId,
        private PaymentMethodType                          $type,
        private ?PaymentMethodMeta                         $meta = null,
        private bool                                       $isDefault = false,
        private PaymentMethodStatus                        $status = PaymentMethodStatus::ACTIVE,
    ) {
    }

    public function paymentMethodIdentifier(): RegisteredPaymentMethodIdentifier
    {
        return $this->paymentMethodIdentifier;
    }

    public function monetizationAccountIdentifier(): MonetizationAccountIdentifier
    {
        return $this->monetizationAccountIdentifier;
    }

    public function paymentMethodId(): PaymentMethodId
    {
        return $this->paymentMethodId;
    }

    public function type(): PaymentMethodType
    {
        return $this->type;
    }

    public function meta(): ?PaymentMethodMeta
    {
        return $this->meta;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function status(): PaymentMethodStatus
    {
        return $this->status;
    }

    public function updateMeta(PaymentMethodMeta $meta): void
    {
        $this->meta = $meta;
    }

    public function markAsDefault(): void
    {
        $this->isDefault = true;
    }

    public function unmarkAsDefault(): void
    {
        $this->isDefault = false;
    }

    public function activate(): void
    {
        $this->status = PaymentMethodStatus::ACTIVE;
    }

    public function deactivate(): void
    {
        $this->status = PaymentMethodStatus::INACTIVE;
    }
}
