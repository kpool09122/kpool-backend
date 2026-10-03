<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use DomainException;
use Throwable;

class PasskeyRecoveryRequiredException extends DomainException
{
    public function __construct(
        string $message = 'Passkey recovery is required.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
