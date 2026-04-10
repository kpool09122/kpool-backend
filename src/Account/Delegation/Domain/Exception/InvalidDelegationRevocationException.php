<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Domain\Exception;

use DomainException;

class InvalidDelegationRevocationException extends DomainException
{
    public function __construct(string $message = 'Only approved delegations can be revoked.')
    {
        parent::__construct($message);
    }
}
