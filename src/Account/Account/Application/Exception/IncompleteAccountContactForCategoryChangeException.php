<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Exception;

use RuntimeException;
use Throwable;

class IncompleteAccountContactForCategoryChangeException extends RuntimeException
{
    public function __construct(
        string $message = 'Account contact information is incomplete for category change request.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
