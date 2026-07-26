<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Domain\Exception;

use DomainException;
use Throwable;

class InvitationNotPendingException extends DomainException
{
    public function __construct(
        string $message = 'Only pending invitations can be revoked.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
