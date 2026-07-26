<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\Exception;

use DomainException;
use Throwable;

class InvalidRollbackTargetVersionException extends DomainException
{
    public function __construct(
        string $message = 'Target version must be less than current version.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
