<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\Exception;

use Exception;

class PolicyNotFoundException extends Exception
{
    public function __construct(
        string $message = 'Policy not found.',
    ) {
        parent::__construct($message, 0);
    }
}
