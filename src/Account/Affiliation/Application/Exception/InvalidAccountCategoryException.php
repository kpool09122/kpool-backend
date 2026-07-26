<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\Exception;

use RuntimeException;
use Throwable;

class InvalidAccountCategoryException extends RuntimeException
{
    public function __construct(
        string $message = 'Invalid Account Category',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
