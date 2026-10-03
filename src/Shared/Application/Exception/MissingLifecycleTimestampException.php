<?php

declare(strict_types=1);

namespace Source\Shared\Application\Exception;

use LogicException;
use Throwable;

class MissingLifecycleTimestampException extends LogicException
{
    public function __construct(
        string $message = 'Lifecycle transition did not set its timestamp.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
