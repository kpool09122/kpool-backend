<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\Exception;

use RuntimeException;

class InconsistentVersionException extends RuntimeException
{
    public function __construct(
        string $message = 'Published wiki versions in the translation set are not consistent.',
    ) {
        parent::__construct($message, 0);
    }
}
