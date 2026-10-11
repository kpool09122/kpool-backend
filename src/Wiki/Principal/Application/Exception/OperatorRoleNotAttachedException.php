<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\Exception;

use RuntimeException;
use Throwable;

class OperatorRoleNotAttachedException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Operations Wiki OperatorsグループにOperatorロールが付与されていません。', 0, $previous);
    }
}
