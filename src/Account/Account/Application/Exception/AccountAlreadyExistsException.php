<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Exception;

use RuntimeException;
use Throwable;

class AccountAlreadyExistsException extends RuntimeException
{
    public function __construct(
        string $message = 'Account Already Exists',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
