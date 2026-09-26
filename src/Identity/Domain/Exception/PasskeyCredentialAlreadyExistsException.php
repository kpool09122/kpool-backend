<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use DomainException;
use Throwable;

class PasskeyCredentialAlreadyExistsException extends DomainException
{
    public function __construct(
        string $message = 'Passkey credential already exists.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
