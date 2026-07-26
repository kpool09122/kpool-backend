<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Exception;

use DomainException;
use Throwable;

class InvalidVerificationApprovalException extends DomainException
{
    public function __construct(
        string $message = 'Only pending verifications can be approved.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
