<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\Exception;

use Exception;
use Throwable;

class PrincipalNotFoundException extends Exception
{
    public function __construct(
        string $message = 'Principal is not found.',
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}
