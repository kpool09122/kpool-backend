<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\Exception;

use RuntimeException;
use Throwable;

class AdministratorGroupIsDefaultException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Operations SiteManagement Administratorsグループはデフォルトグループのため変更できません。', 0, $previous);
    }
}
