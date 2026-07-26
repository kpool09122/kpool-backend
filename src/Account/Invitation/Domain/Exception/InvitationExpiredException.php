<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Domain\Exception;

use DomainException;
use Throwable;

class InvitationExpiredException extends DomainException
{
    public function __construct(
        string $message = 'This invitation link has expired.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
