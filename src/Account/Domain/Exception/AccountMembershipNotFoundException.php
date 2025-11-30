<?php

declare(strict_types=1);

namespace Source\Account\Domain\Exception;

use DomainException;
use Throwable;

class AccountMembershipNotFoundException extends DomainException
{
    public function __construct(
        string $message = 'Account membership is not found.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
