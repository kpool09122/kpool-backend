<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\Exception;

use RuntimeException;

class ExistsApprovedButNotTranslatedMemberException extends RuntimeException
{
    public function __construct(
        string $message = 'There is approved member that has not yet been translated.',
    ) {
        parent::__construct($message, 0);
    }
}
