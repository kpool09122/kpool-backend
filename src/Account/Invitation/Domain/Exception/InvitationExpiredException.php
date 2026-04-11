<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Domain\Exception;

use DomainException;

class InvitationExpiredException extends DomainException
{
    public function __construct(string $message = 'This invitation link has expired.')
    {
        parent::__construct($message);
    }
}
