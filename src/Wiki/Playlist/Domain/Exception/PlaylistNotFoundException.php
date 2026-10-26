<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Domain\Exception;

use DomainException;
use Throwable;

class PlaylistNotFoundException extends DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Playlist not found.', 0, $previous);
    }
}
