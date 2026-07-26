<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\Exception;

use RuntimeException;
use Throwable;

class DisallowedDelegationOperationException extends RuntimeException
{
    public function __construct(
        string $message = 'Disallowed Delegation Operation',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
