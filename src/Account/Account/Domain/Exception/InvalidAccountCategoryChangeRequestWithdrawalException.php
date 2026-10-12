<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Exception;

use DomainException;
use Throwable;

class InvalidAccountCategoryChangeRequestWithdrawalException extends DomainException
{
    public function __construct(
        string $message = 'The account category change request cannot be withdrawn.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
