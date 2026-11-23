<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Exception;

use RuntimeException;
use Throwable;

class AccountSnapshotChangeRequiresApprovalException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Changing an account snapshot after service use requires approval.', 0, $previous);
    }
}
