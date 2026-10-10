<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\Exception;

use RuntimeException;
use Throwable;

class AdministratorRoleNotAttachedException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Operations SiteManagement Administratorsグループにadministratorロールが付与されていません。', 0, $previous);
    }
}
