<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Exception;

use RuntimeException;
use Throwable;

class InvalidAccountCategoryForVerificationException extends RuntimeException
{
    public function __construct(
        string $message = 'Only GENERAL accounts can request verification.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
