<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Exception;

use RuntimeException;
use Throwable;

class InvalidDocumentsForVerificationException extends RuntimeException
{
    public function __construct(
        string $message = 'Invalid or missing documents for verification.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
