<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\Exception;

use RuntimeException;
use Throwable;

class VideoWatchRequestLikeRequiredException extends RuntimeException
{
    public function __construct(
        string $message = 'The video must be liked before sending a watch request.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
