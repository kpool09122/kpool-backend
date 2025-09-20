<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Domain\Exception;

use Exception;

class ExceedMaxRelevantVideoLinksException extends Exception
{
    public function __construct(
        string $message = 'Relevant video links exceed max items',
    ) {
        parent::__construct($message, 0);
    }
}
