<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\Exception;

use RuntimeException;
use Throwable;

class WikiNotFoundException extends RuntimeException
{
    public function __construct(
        string $message = 'Wiki is not found.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
