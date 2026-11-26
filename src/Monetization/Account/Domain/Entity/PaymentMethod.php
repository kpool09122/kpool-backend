<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\Entity;

use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodMeta;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodStatus;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;

class PaymentMethod
{
    public function __construct(
        private readonly PaymentMethodIdentifier $paymentMethodIdentifier,
        private readonly MonetizationAccountIdentifier     $monetizationAccountIdentifier,
        private readonly ExternalPaymentMethodId                   $externalPaymentMethodId,
        private readonly PaymentMethodType                          $type,
        private ?PaymentMethodMeta                         $meta = null,
        private bool                                       $isDefault = false,
        private PaymentMethodStatus                        $status = PaymentMethodStatus::ACTIVE,
    ) {
    }

    public function paymentMethodIdentifier(): PaymentMethodIdentifier
    {
        return $this->paymentMethodIdentifier;
    }

    public function monetizationAccountIdentifier(): MonetizationAccountIdentifier
    {
        return $this->monetizationAccountIdentifier;
    }

    public function externalPaymentMethodId(): ExternalPaymentMethodId
    {
        return $this->externalPaymentMethodId;
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
