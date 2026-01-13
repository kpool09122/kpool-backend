<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Application\Exception;

use RuntimeException;
use Throwable;

class DocumentStorageFailedException extends RuntimeException
{
    public function __construct(string $message = 'Failed to store verification documents.', ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
