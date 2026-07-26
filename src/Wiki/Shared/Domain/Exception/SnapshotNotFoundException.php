<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\Exception;

use DomainException;
use Throwable;

class SnapshotNotFoundException extends DomainException
{
    public function __construct(
        string $message = 'Snapshot is not found.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
