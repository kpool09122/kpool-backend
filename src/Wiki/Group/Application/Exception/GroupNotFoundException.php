<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\Exception;

use Exception;

class GroupNotFoundException extends Exception
{
    public function __construct(
        string $message = 'Group is not found.',
    ) {
        parent::__construct($message, 0);
    }
}
