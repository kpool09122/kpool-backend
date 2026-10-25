<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Domain\Exception;

use DomainException;
use Throwable;

class PlaylistLimitExceededException extends DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('A principal may own at most three playlists.', 0, $previous);
    }
}
