<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Application\Exception;

use RuntimeException;
use Throwable;

class WikiOperationHistoryUnavailableException extends RuntimeException
{
    public function __construct(string $message = 'The requested history cannot be reconstructed completely.', ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
