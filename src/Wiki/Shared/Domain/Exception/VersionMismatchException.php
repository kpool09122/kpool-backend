<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\Exception;

use Exception;

class VersionMismatchException extends Exception
{
    public function __construct(
        string $message = 'Version mismatch detected in translation set.',
    ) {
        parent::__construct($message, 0);
    }
}
