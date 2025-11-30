<?php

declare(strict_types=1);

namespace Source\Account\Domain\ValueObject;

use DateTimeImmutable;

readonly class ContractInfo
{
    public function __construct(
        private BillingAddress $billingAddress,
        private BillingContact $billingContact,
        private BillingMethod $billingMethod,
        private Plan $plan,
        private TaxInfo $taxInfo,
        private ?DateTimeImmutable $billingStartDate = null,
    ) {
    }

    public function billingAddress(): BillingAddress
    {
        return $this->billingAddress;
    }

    public function billingContact(): BillingContact
    {
        return $this->billingContact;
    }

    public function billingMethod(): BillingMethod
    {
        return $this->billingMethod;
    }

    public function plan(): Plan
    {
        return $this->plan;
    }

    public function taxInfo(): TaxInfo
    {
        return $this->taxInfo;
    }

    public function billingStartDate(): ?DateTimeImmutable
    {
        return $this->billingStartDate;
    }
}
