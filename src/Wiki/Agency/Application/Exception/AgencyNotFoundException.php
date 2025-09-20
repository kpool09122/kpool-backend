<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\Exception;

use Exception;

class AgencyNotFoundException extends Exception
{
    public function __construct(
        string $message = 'Agency is not found.',
    ) {
        parent::__construct($message, 0);
    }
}
