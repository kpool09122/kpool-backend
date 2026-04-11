<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Exception;

use Exception;

class PrincipalAlreadyMemberException extends Exception
{
    public function __construct(
        string $message = 'Principal is already a member of this group.',
    ) {
        parent::__construct($message, 0);
    }
}
