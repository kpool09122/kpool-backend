<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\Exception;

use DomainException;
use Throwable;

class VersionMismatchException extends DomainException
{
    public function __construct(
        string $message = 'Version mismatch detected in translation set.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
