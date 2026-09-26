<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use DomainException;
use Throwable;

class ChallengeSessionIdentityMismatchException extends DomainException
{
    public function __construct(
        string $message = 'Challenge session identity does not match.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
