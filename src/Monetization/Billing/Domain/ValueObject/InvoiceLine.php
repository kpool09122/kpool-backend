<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Money;

readonly class InvoiceLine
{
    public function __construct(
        private string $description,
        private Money $unitPrice,
        private int $quantity,
    ) {
        $this->assertDescription($description);
        $this->assertQuantity($quantity);
    }

    public function description(): string
    {
        return $this->description;
    }

    public function unitPrice(): Money
    {
        return $this->unitPrice;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function lineTotal(): Money
    {
        return new Money($this->unitPrice->amount() * $this->quantity, $this->unitPrice->currency());
    }

    private function assertDescription(string $description): void
    {
        if (trim($description) === '') {
            throw new InvalidArgumentException('Description must not be empty.');
        }
    }

    private function assertQuantity(int $quantity): void
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }
    }
}
