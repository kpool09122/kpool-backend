<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Exception;

use DomainException;
use Throwable;

class InvalidAccountSnapshotChangeRequestApprovalException extends DomainException
{
    public function __construct(
        string $message = 'The account snapshot change request cannot be approved.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
