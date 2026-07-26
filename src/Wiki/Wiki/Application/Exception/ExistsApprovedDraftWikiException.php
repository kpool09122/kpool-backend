<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\Exception;

use RuntimeException;
use Throwable;

class ExistsApprovedDraftWikiException extends RuntimeException
{
    public function __construct(
        string $message = 'There is approved draft wiki that has not yet been published.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
