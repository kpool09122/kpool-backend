<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Application\UseCase\Command\CreateInvoice;

use Source\Account\Domain\ValueObject\CountryCode;
use Source\Monetization\Billing\Domain\ValueObject\Discount;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceLine;
use Source\Monetization\Billing\Domain\ValueObject\TaxLine;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;
use Source\Shared\Domain\ValueObject\UserIdentifier;

readonly class CreateInvoiceInput implements CreateInvoiceInputPort
{
    /**
     * @param OrderIdentifier $orderIdentifier
     * @param UserIdentifier $customerIdentifier
     * @param InvoiceLine[] $lines
     * @param Money $shippingCost
     * @param Currency $currency
     * @param Discount|null $discount
     * @param TaxLine[] $taxLines
     * @param CountryCode $sellerCountry
     * @param bool $sellerRegistered
     * @param bool $qualifiedInvoiceRequired
     * @param CountryCode $buyerCountry
     * @param bool $buyerIsBusiness
     * @param bool $paidByCard
     * @param string|null $registrationNumber
     */
    public function __construct(
        private OrderIdentifier $orderIdentifier,
        private UserIdentifier $customerIdentifier,
        private array $lines,
        private Money $shippingCost,
        private Currency $currency,
        private ?Discount $discount,
        private array $taxLines,
        private CountryCode $sellerCountry,
        private bool $sellerRegistered,
        private bool $qualifiedInvoiceRequired,
        private CountryCode $buyerCountry,
        private bool $buyerIsBusiness,
        private bool $paidByCard,
        private ?string $registrationNumber,
    ) {
    }

    public function orderIdentifier(): OrderIdentifier
    {
        return $this->orderIdentifier;
    }

    public function customerIdentifier(): UserIdentifier
    {
        return $this->customerIdentifier;
    }

    /**
     * @return InvoiceLine[]
     */
    public function lines(): array
    {
        return $this->lines;
    }

    public function shippingCost(): Money
    {
        return $this->shippingCost;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    public function discount(): ?Discount
    {
        return $this->discount;
    }

    /**
     * @return TaxLine[]
     */
    public function taxLines(): array
    {
        return $this->taxLines;
    }

    public function sellerCountry(): CountryCode
    {
        return $this->sellerCountry;
    }

    public function sellerRegistered(): bool
    {
        return $this->sellerRegistered;
    }

    public function qualifiedInvoiceRequired(): bool
    {
        return $this->qualifiedInvoiceRequired;
    }

    public function buyerCountry(): CountryCode
    {
        return $this->buyerCountry;
    }

    public function buyerIsBusiness(): bool
    {
        return $this->buyerIsBusiness;
    }

    public function paidByCard(): bool
    {
        return $this->paidByCard;
    }

    public function registrationNumber(): ?string
    {
        return $this->registrationNumber;
    }
}
