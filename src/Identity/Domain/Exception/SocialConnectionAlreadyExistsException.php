<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use Exception;

class SocialConnectionAlreadyExistsException extends Exception
{
    public function __construct(
        string $message = 'Social connection already exists.',
    ) {
        parent::__construct($message, 0);
    }
}
