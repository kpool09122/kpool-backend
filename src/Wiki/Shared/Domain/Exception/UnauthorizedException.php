<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\Exception;

use Exception;

class UnauthorizedException extends Exception
{
    public function __construct()
    {
        parent::__construct('Unauthorized action.');
    }
}
