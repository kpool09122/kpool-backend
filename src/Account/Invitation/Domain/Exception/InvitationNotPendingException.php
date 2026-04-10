<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Domain\Exception;

use DomainException;

class InvitationNotPendingException extends DomainException
{
    public function __construct(string $message = 'Only pending invitations can be revoked.')
    {
        parent::__construct($message);
    }
}
