<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Exception;

use Exception;
use Throwable;

class ContactNotFoundException extends Exception
{
    public function __construct(
        string $message = 'Contact not found',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
