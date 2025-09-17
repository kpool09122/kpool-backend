<?php

declare(strict_types=1);

namespace Businesses\Wiki\Song\UseCase\Exception;

use Exception;

class SongNotFoundException extends Exception
{
    public function __construct(
        string $message = 'Song is not found.',
    ) {
        parent::__construct($message, 0);
    }
}
