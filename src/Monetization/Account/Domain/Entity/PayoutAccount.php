<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\Entity;

use Source\Monetization\Account\Domain\ValueObject\ExternalAccountId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PayoutAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PayoutAccountMeta;
use Source\Monetization\Account\Domain\ValueObject\PayoutAccountStatus;

class PayoutAccount
{
    public function __construct(
        private readonly PayoutAccountIdentifier        $payoutAccountIdentifier,
        private readonly MonetizationAccountIdentifier  $monetizationAccountIdentifier,
        private readonly ExternalAccountId               $externalAccountId,
        private ?PayoutAccountMeta                       $meta = null,
        private bool                                     $isDefault = false,
        private PayoutAccountStatus                      $status = PayoutAccountStatus::ACTIVE,
    ) {
    }

    public function payoutAccountIdentifier(): PayoutAccountIdentifier
    {
        return $this->payoutAccountIdentifier;
    }

    public function monetizationAccountIdentifier(): MonetizationAccountIdentifier
    {
        return $this->monetizationAccountIdentifier;
    }

    public function externalAccountId(): ExternalAccountId
    {
        return $this->externalAccountId;
    }

    public function meta(): ?PayoutAccountMeta
    {
        return $this->meta;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function status(): PayoutAccountStatus
    {
        return $this->status;
    }

    public function updateMeta(PayoutAccountMeta $meta): void
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
        $this->status = PayoutAccountStatus::ACTIVE;
    }

    public function deactivate(): void
    {
        $this->status = PayoutAccountStatus::INACTIVE;
    }
}
