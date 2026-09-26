<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Exception;

use RuntimeException;
use Throwable;

class AccountCategoryChangeRequestAlreadyPendingException extends RuntimeException
{
    public function __construct(
        string $message = 'Account category change request is already pending.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
