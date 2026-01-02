<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\Exception;

use Exception;

class InvalidRollbackTargetVersionException extends Exception
{
    public function __construct(
        string $message = 'Target version must be less than current version.',
    ) {
        parent::__construct($message, 0);
    }
}
