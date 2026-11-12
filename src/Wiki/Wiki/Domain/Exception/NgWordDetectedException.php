<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\Exception;

use RuntimeException;
use Throwable;

final class NgWordDetectedException extends RuntimeException
{
    public function __construct(
        string $message = 'The wiki content contains a prohibited word.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
