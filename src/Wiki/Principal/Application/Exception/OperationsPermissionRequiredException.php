<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\Exception;

use RuntimeException;
use Throwable;

class OperationsPermissionRequiredException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('対象IdentityはAccountのOperations権限を持っていません。', 0, $previous);
    }
}
