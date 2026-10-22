<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLinkAutoCollection\Domain\Exception;

use RuntimeException;
use Throwable;

class VideoSearchFailedException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Video search failed.', 0, $previous);
    }
}
