<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Application\Exception;

use RuntimeException;
use Throwable;

class DuplicateSlugException extends RuntimeException
{
    public function __construct(
        string $message = 'Slug already exists.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
