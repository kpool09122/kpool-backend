<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\Exception;

use RuntimeException;
use Throwable;

class VideoWatchRequestDurationNotEligibleException extends RuntimeException
{
    public function __construct(
        string $message = 'The video duration is not eligible for watch requests.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
