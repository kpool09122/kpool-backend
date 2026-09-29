<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use DomainException;
use Throwable;

class WebAuthnVerificationException extends DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('WebAuthn response verification failed.', 0, $previous);
    }
}
