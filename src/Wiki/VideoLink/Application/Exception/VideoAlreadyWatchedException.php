<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\Exception;

use RuntimeException;
use Throwable;

class VideoAlreadyWatchedException extends RuntimeException
{
    public function __construct(
        string $message = 'The official owner has already watched this video.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
