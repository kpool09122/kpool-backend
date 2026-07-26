<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Exception;

use RuntimeException;
use Throwable;

class ContactNotFoundException extends RuntimeException
{
    public function __construct(
        string $message = 'Contact not found',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
