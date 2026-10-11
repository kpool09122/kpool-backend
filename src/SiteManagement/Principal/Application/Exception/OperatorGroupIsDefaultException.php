<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\Exception;

use RuntimeException;
use Throwable;

class OperatorGroupIsDefaultException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Operations SiteManagement Operatorsグループはデフォルトグループのため変更できません。', 0, $previous);
    }
}
