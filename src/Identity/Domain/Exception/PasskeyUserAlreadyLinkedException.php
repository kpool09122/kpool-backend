<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use DomainException;
use Throwable;

class PasskeyUserAlreadyLinkedException extends DomainException
{
    public function __construct(
        string $message = 'Passkey user is already linked to an identity.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
