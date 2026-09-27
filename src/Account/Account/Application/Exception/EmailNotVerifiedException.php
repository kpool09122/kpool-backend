<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Exception;

use RuntimeException;
use Throwable;

class EmailNotVerifiedException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('メール認証済みのIdentityを指定してください。', 0, $previous);
    }
}
