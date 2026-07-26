<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Domain\Exception;

use DomainException;
use Throwable;

class InvitationAlreadyUsedOrRevokedException extends DomainException
{
    public function __construct(
        string $message = 'This invitation has already been used or revoked.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
