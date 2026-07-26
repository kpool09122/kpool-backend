<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\Exception;

use RuntimeException;
use Throwable;

class DisallowedAffiliationOperationException extends RuntimeException
{
    public function __construct(
        string $message = 'Disallowed Affiliation Operation',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
