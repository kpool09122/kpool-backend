<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Exception;

use Exception;

class PrincipalNotMemberException extends Exception
{
    public function __construct(
        string $message = 'Principal is not a member of this group.',
    ) {
        parent::__construct($message, 0);
    }
}
