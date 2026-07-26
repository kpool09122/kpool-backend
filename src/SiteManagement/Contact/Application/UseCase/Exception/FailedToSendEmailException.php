<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Exception;

use RuntimeException;
use Throwable;

class FailedToSendEmailException extends RuntimeException
{
    public function __construct(
        string $message = 'Sending email is failed.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
