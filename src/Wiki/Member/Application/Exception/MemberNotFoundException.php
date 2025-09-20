<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\Exception;

use Exception;

class MemberNotFoundException extends Exception
{
    public function __construct(
        string $message = 'Member is not found.',
    ) {
        parent::__construct($message, 0);
    }
}
