<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\ValueObject;

readonly class PayoutBankMeta
{
    public function __construct(
        private ?string            $bankName = null,
        private ?string            $last4 = null,
        private ?string            $country = null,
        private ?string            $currency = null,
        private ?AccountHolderType $accountHolderType = null,
    ) {
    }

    public function bankName(): ?string
    {
        return $this->bankName;
    }

    public function last4(): ?string
    {
        return $this->last4;
    }

    public function country(): ?string
    {
        return $this->country;
    }

    public function currency(): ?string
    {
        return $this->currency;
    }

    public function accountHolderType(): ?AccountHolderType
    {
        return $this->accountHolderType;
    }
}
