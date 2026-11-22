<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Application\Exception;

use RuntimeException;
use Throwable;

class CannotRemoveLastMonetizationAdministratorException extends RuntimeException
{
    public function __construct(
        string $message = 'Cannot remove the last reachable Monetization administrator.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
