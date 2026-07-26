<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Exception;

use DomainException;
use Throwable;

class InvalidVerificationRejectionException extends DomainException
{
    public function __construct(
        string $message = 'Only pending verifications can be rejected.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
