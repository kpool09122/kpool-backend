<?php

declare(strict_types=1);

namespace Source\Account\Domain\Exception;

use DomainException;
use Throwable;

class DisallowedToWithdrawByOwnerException extends DomainException
{
    public function __construct(
        string $message = 'Owner is not allowed to withdraw from membership.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
