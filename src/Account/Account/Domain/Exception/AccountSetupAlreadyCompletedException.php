<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Exception;

use DomainException;
use Throwable;

class AccountSetupAlreadyCompletedException extends DomainException
{
    public function __construct(
        string $message = 'Account setup has already been completed.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
