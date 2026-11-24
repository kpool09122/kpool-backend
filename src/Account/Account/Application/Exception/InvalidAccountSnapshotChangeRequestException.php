<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Exception;

use RuntimeException;
use Throwable;

class InvalidAccountSnapshotChangeRequestException extends RuntimeException
{
    public function __construct(
        string $message = 'Account snapshot change request cannot be submitted while a pending request exists.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
