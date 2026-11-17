<?php

declare(strict_types=1);

namespace Application\Mcp;

use RuntimeException;
use Throwable;

final class ApiFailure extends RuntimeException
{
    public function __construct(public readonly int $status, public readonly string $errorCode, public readonly ?int $retryAfter = null, ?Throwable $previous = null)
    {
        parent::__construct($errorCode, 0, $previous);
    }
}
