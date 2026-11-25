<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Exception;

use RuntimeException;
use Throwable;

class NoAccountSnapshotChangesException extends RuntimeException
{
    public function __construct(
        string $message = 'The account profile and document contents have not changed.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
