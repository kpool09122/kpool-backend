<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\Exception;

use RuntimeException;
use Throwable;

class PublishedWikiRequiredException extends RuntimeException
{
    public function __construct(string $message = 'Same-version publication requires a linked published wiki.', ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
