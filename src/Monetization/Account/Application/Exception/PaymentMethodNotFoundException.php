<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\Exception;

use RuntimeException;
use Throwable;

class PaymentMethodNotFoundException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Payment method not found.', 0, $previous);
    }
}
