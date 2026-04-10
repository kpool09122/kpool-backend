<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Domain\Exception;

use DomainException;

class InvalidDelegationApprovalException extends DomainException
{
    public function __construct(string $message = 'Only pending delegations can be approved.')
    {
        parent::__construct($message);
    }
}
