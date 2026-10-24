<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\Exception;

use RuntimeException;
use Throwable;

class VideoLinkNotFoundException extends RuntimeException
{
    public function __construct(
        string $message = 'Video link not found',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
