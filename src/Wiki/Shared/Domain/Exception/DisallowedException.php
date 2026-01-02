<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\Exception;

use Exception;

class DisallowedException extends Exception
{
    public function __construct(
        string $message = 'Action is not allowed for this principal.',
    ) {
        parent::__construct($message, 0);
    }
}
