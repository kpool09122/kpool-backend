<?php

namespace Businesses\Wiki\Agency\UseCase\Exception;

use Exception;

class AgencyNotFoundException extends Exception
{
    public function __construct(
        string $message = 'Agency is not found.',
    ) {
        parent::__construct($message, 0);
    }
}
