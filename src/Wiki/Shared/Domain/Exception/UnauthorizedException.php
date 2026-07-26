<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\Exception;

use DomainException;
use Throwable;

/**
 * @deprecated
 */
class UnauthorizedException extends DomainException
{
    public function __construct(
        string $message = 'Unauthorized action.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
