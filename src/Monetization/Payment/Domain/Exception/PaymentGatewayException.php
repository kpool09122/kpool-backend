<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Domain\Exception;

use DomainException;
use Throwable;

class PaymentGatewayException extends DomainException
{
    public function __construct(
        string $message = 'Payment gateway operation failed.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
