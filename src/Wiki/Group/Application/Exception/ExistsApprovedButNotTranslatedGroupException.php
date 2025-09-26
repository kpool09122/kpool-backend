<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\Exception;

use RuntimeException;

class ExistsApprovedButNotTranslatedGroupException extends RuntimeException
{
    public function __construct(
        string $message = 'There is approved group that has not yet been translated.',
    ) {
        parent::__construct($message, 0);
    }
}
