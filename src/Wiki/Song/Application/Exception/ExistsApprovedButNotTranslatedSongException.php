<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\Exception;

use RuntimeException;

class ExistsApprovedButNotTranslatedSongException extends RuntimeException
{
    public function __construct(
        string $message = 'There is approved song that has not yet been translated.',
    ) {
        parent::__construct($message, 0);
    }
}
