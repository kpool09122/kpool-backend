<?php

namespace Businesses\Group\UseCase\Exception;

use Exception;

class GroupNotFoundException extends Exception
{
    public function __construct(
        string $message = 'Group is not found.',
    ) {
        parent::__construct($message, 0);
    }
}
