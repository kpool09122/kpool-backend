<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\Exception;

use RuntimeException;

class ExistsApprovedButNotTranslatedTalentException extends RuntimeException
{
    public function __construct(
        string $message = 'There is approved member that has not yet been translated.',
    ) {
        parent::__construct($message, 0);
    }
}
