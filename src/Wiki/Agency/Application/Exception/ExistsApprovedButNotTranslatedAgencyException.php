<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\Exception;

use RuntimeException;

class ExistsApprovedButNotTranslatedAgencyException extends RuntimeException
{
    public function __construct(
        string $message = 'There is approved agency that has not yet been translated.',
    ) {
        parent::__construct($message, 0);
    }
}
