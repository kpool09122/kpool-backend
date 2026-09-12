<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\Exception;

use RuntimeException;
use Throwable;

class PrincipalAlreadyAssignedToPrincipalGroupException extends RuntimeException
{
    public function __construct(
        string $message = 'Principal is already assigned to a principal group.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
