<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Exception;

use DomainException;
use Throwable;

class AccountSetupUnavailableException extends DomainException
{
    public function __construct(
        string $message = 'Account setup is unavailable for the current account state.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
