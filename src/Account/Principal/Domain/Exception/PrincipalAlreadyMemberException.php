<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\Exception;

use DomainException;

class PrincipalAlreadyMemberException extends DomainException
{
    public function __construct(
        string $message = 'Principal is already a member of this group.',
    ) {
        parent::__construct($message, 0);
    }
}
