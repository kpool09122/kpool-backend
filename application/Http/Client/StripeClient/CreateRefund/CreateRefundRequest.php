<?php

declare(strict_types=1);

namespace Application\Http\Client\StripeClient\CreateRefund;

final readonly class CreateRefundRequest
{
    /**
     * @param array<string, string> $metadata
     */
    public function __construct(
        private string $paymentIntentId,
        private int $amount,
        private string $reason,
        private array $metadata = [],
    ) {
    }

    public function paymentIntentId(): string
    {
        return $this->paymentIntentId;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    /**
     * @return array<string, string>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }
}
