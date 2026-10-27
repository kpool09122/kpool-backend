<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\Exception;

use RuntimeException;
use Throwable;

class VideoWatchRequestResendNotAvailableException extends RuntimeException
{
    public function __construct(
        string $message = 'Seven days must pass before resending a watch request for the same video.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
