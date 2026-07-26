<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\Exception;

use RuntimeException;
use Throwable;

class CannotDeleteLastOwnerGroupException extends RuntimeException
{
    public function __construct(
        string $message = 'Cannot delete the last OWNER group with members.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
