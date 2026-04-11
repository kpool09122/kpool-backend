<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Domain\Exception;

use DomainException;

class InvitationAlreadyUsedOrRevokedException extends DomainException
{
    public function __construct(string $message = 'This invitation has already been used or revoked.')
    {
        parent::__construct($message);
    }
}
