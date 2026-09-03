<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Domain\Exception;

use DomainException;
use Throwable;

class AccountDelegationAlreadyExistsException extends DomainException
{
    public function __construct(
        string $message = 'An active delegation request already exists.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
