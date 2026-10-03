<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use DomainException;
use Throwable;

class PasskeyBackupEligibilityChangedException extends DomainException
{
    public function __construct(
        string $message = 'Passkey backup eligibility cannot change.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
