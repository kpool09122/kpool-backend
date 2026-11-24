<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Exception;

use DomainException;
use Throwable;

class InvalidAccountSnapshotChangeRequestRejectionException extends DomainException
{
    public function __construct(
        string $message = 'The account snapshot change request cannot be rejected.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
