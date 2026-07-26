<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\Exception;

use RuntimeException;
use Throwable;

class PrincipalGroupNotFoundException extends RuntimeException
{
    public function __construct(
        string $message = 'PrincipalGroup is not found.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
