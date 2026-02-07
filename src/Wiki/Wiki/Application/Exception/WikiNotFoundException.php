<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\Exception;

use Exception;

class WikiNotFoundException extends Exception
{
    public function __construct(
        string $message = 'Wiki is not found.',
    ) {
        parent::__construct($message, 0);
    }
}
