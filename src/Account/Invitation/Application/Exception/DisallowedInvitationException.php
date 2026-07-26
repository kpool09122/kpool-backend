<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Application\Exception;

use RuntimeException;
use Throwable;

class DisallowedInvitationException extends RuntimeException
{
    public function __construct(
        string $message = 'Disallowed Invitation',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
