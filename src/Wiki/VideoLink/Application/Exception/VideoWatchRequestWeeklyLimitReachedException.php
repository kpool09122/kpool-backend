<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\Exception;

use RuntimeException;
use Throwable;

class VideoWatchRequestWeeklyLimitReachedException extends RuntimeException
{
    public function __construct(
        string $message = 'The watch request limit for this talent in the last seven days has been reached.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
