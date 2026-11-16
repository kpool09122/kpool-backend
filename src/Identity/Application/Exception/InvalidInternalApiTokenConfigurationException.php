<?php

declare(strict_types=1);

namespace Source\Identity\Application\Exception;

use RuntimeException;
use Throwable;

class InvalidInternalApiTokenConfigurationException extends RuntimeException
{
    public function __construct(string $message = 'Internal API token configuration is invalid.', ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
