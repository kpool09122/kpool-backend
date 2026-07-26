<?php

declare(strict_types=1);

namespace Source\Shared\Application\Exception;

use RuntimeException;
use Throwable;

class InvalidRemoteImageException extends RuntimeException
{
    public function __construct(
        string $message = 'Invalid Remote Image',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
