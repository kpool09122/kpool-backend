<?php

declare(strict_types=1);

namespace Source\Account\IdentityGroup\Domain\Exception;

use DomainException;

class IdentityAlreadyMemberException extends DomainException
{
    public function __construct(
        string $message = 'Identity is already a member of this group.',
    ) {
        parent::__construct($message, 0);
    }
}
