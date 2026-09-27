<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\Exception;

use RuntimeException;
use Throwable;

class OperationsMembershipNotFoundException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('対象IdentityはOperationsグループに所属していないため、権限を剥奪できません。', 0, $previous);
    }
}
