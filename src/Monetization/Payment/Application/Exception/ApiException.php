<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Application\Exception;

use RuntimeException;
use Stripe\Exception\ApiErrorException;
use Throwable;

class ApiException extends RuntimeException
{
    public function __construct(
        string $message = 'Api',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function from(ApiErrorException $e): self
    {
        return new self(
            $e->getMessage(),
            $e
        );
    }
}
