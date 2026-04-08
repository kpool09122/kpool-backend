<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\ValueObject;

readonly class PaymentMethodMeta
{
    public function __construct(
        private ?string $brand = null,
        private ?string $last4 = null,
        private ?int    $expMonth = null,
        private ?int    $expYear = null,
    ) {
    }

    public function brand(): ?string
    {
        return $this->brand;
    }

    public function last4(): ?string
    {
        return $this->last4;
    }

    public function expMonth(): ?int
    {
        return $this->expMonth;
    }

    public function expYear(): ?int
    {
        return $this->expYear;
    }
}
