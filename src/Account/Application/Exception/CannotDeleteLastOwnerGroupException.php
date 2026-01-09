<?php

declare(strict_types=1);

namespace Source\Account\Application\Exception;

use Exception;

class CannotDeleteLastOwnerGroupException extends Exception
{
    public function __construct(
        string $message = 'Cannot delete the last OWNER group with members.',
    ) {
        parent::__construct($message, 0);
    }
}
