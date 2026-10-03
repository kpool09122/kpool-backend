<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Exception;

use DomainException;
use Throwable;

class IdentityWithdrawalNotAllowedException extends DomainException
{
    public function __construct(
        string $message = 'This account membership does not permit self-service withdrawal.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
