<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\Exception;

use LogicException;
use Throwable;

class ImmutableReadModelException extends LogicException
{
    public function __construct(
        string $message = 'ReadModel is immutable.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
