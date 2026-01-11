<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Application\UseCase\Command\CreateInvoice;

use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Billing\Domain\ValueObject\Discount;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceLine;
use Source\Monetization\Billing\Domain\ValueObject\TaxLine;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;

interface CreateInvoiceInputPort
{
    public function orderIdentifier(): OrderIdentifier;

    public function buyerMonetizationAccountIdentifier(): MonetizationAccountIdentifier;

    /**
     * @return InvoiceLine[]
     */
    public function lines(): array;

    public function shippingCost(): Money;

    public function currency(): Currency;

    public function discount(): ?Discount;

    /**
     * @return TaxLine[]
     */
    public function taxLines(): array;

    public function sellerCountry(): CountryCode;

    public function sellerRegistered(): bool;

    public function qualifiedInvoiceRequired(): bool;

    public function buyerCountry(): CountryCode;

    public function buyerIsBusiness(): bool;

    public function paidByCard(): bool;

    public function registrationNumber(): ?string;
}
