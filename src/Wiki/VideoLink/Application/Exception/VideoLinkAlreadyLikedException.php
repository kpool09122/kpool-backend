<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\Exception;

use RuntimeException;
use Throwable;

class VideoLinkAlreadyLikedException extends RuntimeException
{
    public function __construct(
        string $message = 'Video link already liked',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
