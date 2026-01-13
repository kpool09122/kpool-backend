<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Application\Exception;

use RuntimeException;

class InvalidDocumentsForVerificationException extends RuntimeException
{
    public function __construct(string $message = 'Invalid or missing documents for verification.')
    {
        parent::__construct($message);
    }
}
