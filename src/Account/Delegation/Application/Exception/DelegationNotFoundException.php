<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\Exception;

use RuntimeException;
use Throwable;

class DelegationNotFoundException extends RuntimeException
{
    public function __construct(
        string $message = 'Delegation Not Found',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
