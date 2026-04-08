<?php

declare(strict_types=1);

namespace Application\Http\Client\StripeClient\RetrievePaymentMethod;

final readonly class RetrievePaymentMethodResponse
{
    public function __construct(
        private string $id,
        private string $type,
        private ?string $brand,
        private ?string $last4,
        private ?int $expMonth,
        private ?int $expYear,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function type(): string
    {
        return $this->type;
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
