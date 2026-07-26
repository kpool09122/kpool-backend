<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\Exception;

use RuntimeException;
use Throwable;

class AffiliationAlreadyExistsException extends RuntimeException
{
    public function __construct(
        string $message = 'Affiliation Already Exists',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
