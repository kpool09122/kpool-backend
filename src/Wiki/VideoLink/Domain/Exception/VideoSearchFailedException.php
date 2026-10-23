<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Exception;

use RuntimeException;
use Throwable;

class VideoSearchFailedException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct($previous?->getMessage() ?? 'Video search failed.', 0, $previous);
    }
}
