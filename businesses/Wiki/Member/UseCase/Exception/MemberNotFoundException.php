<?php

namespace Businesses\Wiki\Member\UseCase\Exception;

use Exception;

class MemberNotFoundException extends Exception
{
    public function __construct(
        string $message = 'Member is not found.',
    ) {
        parent::__construct($message, 0);
    }
}
