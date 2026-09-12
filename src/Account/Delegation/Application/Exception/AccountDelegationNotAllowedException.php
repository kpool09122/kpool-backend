<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\Exception;

use RuntimeException;
use Throwable;

class AccountDelegationNotAllowedException extends RuntimeException
{
    public function __construct(
        string $message = 'Delegation request is not allowed.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
