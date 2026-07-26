<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Domain\Exception;

use DomainException;
use Throwable;

class InvalidDelegationRevocationException extends DomainException
{
    public function __construct(
        string $message = 'Only approved delegations can be revoked.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
