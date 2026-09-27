<?php

declare(strict_types=1);

namespace Source\Shared\Application\Exception;

use LogicException;
use Throwable;

class OutputNotInitializedException extends LogicException
{
    public function __construct(
        string $message = 'Use case output has not been initialized.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
