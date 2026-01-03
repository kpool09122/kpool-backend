<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Infrastructure\Exception;

use Source\Monetization\Payment\Domain\Exception\PaymentGatewayException;
use Stripe\Exception\ApiErrorException;

class StripeApiException extends PaymentGatewayException
{
    public static function fromStripeException(ApiErrorException $e): self
    {
        $error = $e->getError();

        return new self(
            $e->getMessage(),
            $error?->code,
            $e
        );
    }
}
