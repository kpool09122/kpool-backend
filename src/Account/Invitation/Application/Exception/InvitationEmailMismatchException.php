<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Application\Exception;

use RuntimeException;
use Throwable;

class InvitationEmailMismatchException extends RuntimeException
{
    public function __construct(
        string $message = 'The invitation email does not match the registered email.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
