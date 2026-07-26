<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Domain\Exception;

use DomainException;
use Throwable;

class InvalidDelegationApprovalException extends DomainException
{
    public function __construct(
        string $message = 'Only pending delegations can be approved.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
