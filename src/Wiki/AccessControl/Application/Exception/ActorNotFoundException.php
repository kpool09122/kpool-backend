<?php

declare(strict_types=1);

namespace Source\Wiki\AccessControl\Application\Exception;

use Exception;

class ActorNotFoundException extends Exception
{
    public function __construct(
        string $message = 'Actor is not found.',
    ) {
        parent::__construct($message, 0);
    }
}
