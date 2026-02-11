<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\Exception;

use RuntimeException;

class ExistsApprovedDraftWikiException extends RuntimeException
{
    public function __construct(
        string $message = 'There is approved draft wiki that has not yet been published.',
    ) {
        parent::__construct($message, 0);
    }
}
