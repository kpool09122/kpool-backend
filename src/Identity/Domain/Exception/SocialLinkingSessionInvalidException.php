<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use DomainException;
use Throwable;

class SocialLinkingSessionInvalidException extends DomainException
{
    public function __construct(
        string $message = 'SSO linking session is invalid or expired.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
