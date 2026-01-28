<?php

declare(strict_types=1);

namespace Application\Http\Client\StripeClient\CreatePaymentIntent;

final readonly class CreatePaymentIntentResponse
{
    public function __construct(
        private string $id,
        private string $status,
    ) {
    }

    public function params(): CreatePaymentIntentParams
    {
        return CreatePaymentIntentParams::fromArray([
            'id' => $this->id,
            'status' => $this->status,
        ]);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function status(): string
    {
        return $this->status;
    }
}
