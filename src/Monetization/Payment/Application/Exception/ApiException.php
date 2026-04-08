<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Application\Exception;

use Source\Monetization\Payment\Domain\Exception\PaymentGatewayException;
use Stripe\Exception\ApiErrorException;

class ApiException extends PaymentGatewayException
{
    public static function from(ApiErrorException $e): self
    {
        return new self(
            $e->getMessage(),
            $e
        );
    }
}
