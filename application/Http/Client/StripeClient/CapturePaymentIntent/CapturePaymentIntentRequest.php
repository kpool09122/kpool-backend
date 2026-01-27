<?php

declare(strict_types=1);

namespace Application\Http\Client\StripeClient\CapturePaymentIntent;

final readonly class CapturePaymentIntentRequest
{
    public function __construct(
        private string $paymentIntentId,
        private int $amountToCapture,
    ) {
    }

    public function paymentIntentId(): string
    {
        return $this->paymentIntentId;
    }

    public function amountToCapture(): int
    {
        return $this->amountToCapture;
    }
}
