<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Application\Exception;

use RuntimeException;
use Throwable;

class InvitationNotFoundException extends RuntimeException
{
    public function __construct(
        string $message = 'Invitation Not Found',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
