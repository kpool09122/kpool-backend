<?php

declare(strict_types=1);

namespace Businesses\SiteManagement\Contact\UseCase\Exception;

use RuntimeException;

class FailedToSendEmailException extends RuntimeException
{
    public function __construct(
        string $message = 'Sending email is failed.',
    ) {
        parent::__construct($message, 0);
    }
}
