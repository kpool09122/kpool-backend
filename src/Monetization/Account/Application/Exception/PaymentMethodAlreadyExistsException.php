<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\Exception;

use RuntimeException;
use Throwable;

class PaymentMethodAlreadyExistsException extends RuntimeException
{
    public function __construct(
        string $message = 'Payment method already exists.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
