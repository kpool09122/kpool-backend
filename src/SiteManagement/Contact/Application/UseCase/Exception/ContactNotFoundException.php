<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Application\UseCase\Exception;

use Exception;

class ContactNotFoundException extends Exception
{
    public function __construct(string $message = 'Contact not found')
    {
        parent::__construct($message);
    }
}
