<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\Exception;

use Exception;

class SnapshotNotFoundException extends Exception
{
    public function __construct(
        string $message = 'Snapshot is not found.',
    ) {
        parent::__construct($message, 0);
    }
}
