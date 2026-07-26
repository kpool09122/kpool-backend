<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\Exception;

use RuntimeException;
use Throwable;

class AffiliationNotFoundException extends RuntimeException
{
    public function __construct(
        string $message = 'Affiliation Not Found',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
