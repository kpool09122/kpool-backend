<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\Exception;

use RuntimeException;
use Throwable;

class AdministratorMembershipNotFoundException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('対象IdentityはOperations Wiki Administratorsグループに所属していません。', 0, $previous);
    }
}
