<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Exception;

use RuntimeException;
use Throwable;

class SameAccountCategoryChangeRequestException extends RuntimeException
{
    public function __construct(
        string $message = 'Requested account category is same as current account category.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
