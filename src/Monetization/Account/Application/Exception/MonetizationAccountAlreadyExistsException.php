<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\Exception;

use RuntimeException;
use Throwable;

class MonetizationAccountAlreadyExistsException extends RuntimeException
{
    public function __construct(
        string $message = 'Monetization account already exists.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
