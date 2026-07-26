<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Domain\Exception;

use DomainException;
use Throwable;

class InvalidAffiliationTerminationException extends DomainException
{
    public function __construct(
        string $message = 'Invalid Affiliation Termination',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
