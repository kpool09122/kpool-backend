<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\Exception;

use DomainException;
use Throwable;

class PrincipalAlreadyMemberException extends DomainException
{
    public function __construct(
        string $message = 'Principal is already a member of this group.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
