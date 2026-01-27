<?php

declare(strict_types=1);

namespace Application\Http\Client\StripeClient\CreateTransfer;

final readonly class CreateTransferRequest
{
    /**
     * @param array<string, string> $metadata
     */
    public function __construct(
        private int $amount,
        private string $currency,
        private string $destination,
        private array $metadata = [],
    ) {
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function destination(): string
    {
        return $this->destination;
    }

    /**
     * @return array<string, string>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }
}
