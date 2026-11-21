<?php

declare(strict_types=1);

namespace Source\Monetization\Shared\Domain\Exception;

use RuntimeException;
use Throwable;

class UnauthorizedException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Monetization operation is not authorized.', 0, $previous);
    }
}
