<?php

declare(strict_types=1);

namespace Source\Shared\Application\Exception;

use RuntimeException;
use Throwable;

class YouTubeRequestFailedException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Video provider request failed.', 0, $previous);
    }
}
