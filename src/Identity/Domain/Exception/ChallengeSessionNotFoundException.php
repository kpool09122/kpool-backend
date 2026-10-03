<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use DomainException;
use Throwable;

class ChallengeSessionNotFoundException extends DomainException
{
    public function __construct(
        string $message = 'Challenge session is invalid or expired.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
