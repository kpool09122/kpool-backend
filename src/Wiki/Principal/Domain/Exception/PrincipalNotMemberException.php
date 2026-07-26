<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Exception;

use DomainException;
use Throwable;

class PrincipalNotMemberException extends DomainException
{
    public function __construct(
        string $message = 'Principal is not a member of this group.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
