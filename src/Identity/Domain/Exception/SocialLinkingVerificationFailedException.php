<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use DomainException;
use Throwable;

class SocialLinkingVerificationFailedException extends DomainException
{
    public function __construct(
        string $message = 'SSO linking email verification failed.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
