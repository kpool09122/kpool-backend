<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Exception;

use RuntimeException;
use Throwable;

class VideoLinkCollectionFailedException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Video collection could not be saved.', 0, $previous);
    }
}
