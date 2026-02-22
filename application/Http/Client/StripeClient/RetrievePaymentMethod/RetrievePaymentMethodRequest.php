<?php

declare(strict_types=1);

namespace Application\Http\Client\StripeClient\RetrievePaymentMethod;

final readonly class RetrievePaymentMethodRequest
{
    public function __construct(
        private string $paymentMethodId,
    ) {
    }

    public function paymentMethodId(): string
    {
        return $this->paymentMethodId;
    }
}
