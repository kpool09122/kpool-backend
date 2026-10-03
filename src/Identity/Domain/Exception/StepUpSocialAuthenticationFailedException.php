<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use DomainException;
use Throwable;

class StepUpSocialAuthenticationFailedException extends DomainException
{
    public function __construct(
        string $message = 'Social step-up authentication failed.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
