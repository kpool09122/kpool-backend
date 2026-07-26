<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Exception;

use RuntimeException;
use Throwable;

class AccountVerificationAlreadyRequestedException extends RuntimeException
{
    public function __construct(
        string $message = 'A verification request is already pending or under review.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
