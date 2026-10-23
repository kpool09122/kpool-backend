<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Exception;

use DomainException;
use Throwable;

class VideoLinkAlreadyExistsException extends DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Video link already exists.', 0, $previous);
    }
}
