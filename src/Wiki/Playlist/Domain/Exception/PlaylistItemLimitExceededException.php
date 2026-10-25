<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Domain\Exception;

use DomainException;
use Throwable;

class PlaylistItemLimitExceededException extends DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('A playlist may contain at most fifty videos.', 0, $previous);
    }
}
