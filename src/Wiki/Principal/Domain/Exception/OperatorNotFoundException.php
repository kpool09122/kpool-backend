<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Exception;

use Exception;

class OperatorNotFoundException extends Exception
{
    public function __construct(
        string $message = 'Actor is not found.',
    ) {
        parent::__construct($message, 0);
    }
}
