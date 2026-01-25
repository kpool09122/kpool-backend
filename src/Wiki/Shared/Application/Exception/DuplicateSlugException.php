<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Application\Exception;

use Exception;

class DuplicateSlugException extends Exception
{
    public function __construct(
        string $message = 'Slug already exists.',
    ) {
        parent::__construct($message, 0);
    }
}
