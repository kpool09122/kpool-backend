<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\Exception;

use DomainException;

class PrincipalNotMemberException extends DomainException
{
    public function __construct(
        string $message = 'Principal is not a member of this group.',
    ) {
        parent::__construct($message, 0);
    }
}
