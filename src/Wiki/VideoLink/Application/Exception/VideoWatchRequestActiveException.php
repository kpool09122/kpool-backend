<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\Exception;

use RuntimeException;
use Throwable;

class VideoWatchRequestActiveException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('An active video watch request prevents this operation.', 0, $previous);
    }
}
