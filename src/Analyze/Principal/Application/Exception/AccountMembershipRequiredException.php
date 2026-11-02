<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Application\Exception;

use RuntimeException;
use Throwable;

class AccountMembershipRequiredException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Active account membership is required.', 0, $previous);
    }
}
