<?php

declare(strict_types=1);

namespace Application\Http\Client\StripeClient\CreatePaymentIntent;

final readonly class CreatePaymentIntentRequest
{
    /**
     * @param string[] $paymentMethodTypes
     * @param array<string, string> $metadata
     */
    public function __construct(
        private int $amount,
        private string $currency,
        private string $customerId,
        private string $paymentMethodId,
        private array $paymentMethodTypes,
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

    public function customerId(): string
    {
        return $this->customerId;
    }

    public function paymentMethodId(): string
    {
        return $this->paymentMethodId;
    }

    /**
     * @return string[]
     */
    public function paymentMethodTypes(): array
    {
        return $this->paymentMethodTypes;
    }

    /**
     * @return array<string, string>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }
}
