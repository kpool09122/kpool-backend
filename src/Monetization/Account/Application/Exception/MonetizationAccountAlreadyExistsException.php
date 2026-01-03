<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\Exception;

use Exception;

class MonetizationAccountAlreadyExistsException extends Exception
{
    public function __construct(
        string $message = 'Monetization account already exists.',
    ) {
        parent::__construct($message, 0);
    }
}
