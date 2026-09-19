<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use DomainException;
use Throwable;

class InvalidPasskeyBackupStateException extends DomainException
{
    public function __construct(
        string $message = 'Passkey backup state is invalid.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
