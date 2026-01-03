<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Domain\Exception;

use DomainException;
use Throwable;

class PaymentGatewayException extends DomainException
{
    public function __construct(
        string $message,
        private readonly ?string $gatewayErrorCode = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function gatewayErrorCode(): ?string
    {
        return $this->gatewayErrorCode;
    }
}
