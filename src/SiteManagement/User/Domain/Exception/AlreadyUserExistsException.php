<?php

declare(strict_types=1);

namespace Source\SiteManagement\User\Domain\Exception;

use Exception;
use Throwable;

class AlreadyUserExistsException extends Exception
{
    public function __construct(
        string $message = 'User already exists.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
