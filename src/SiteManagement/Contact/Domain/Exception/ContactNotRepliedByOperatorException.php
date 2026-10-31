<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Domain\Exception;

use DomainException;
use Throwable;

class ContactNotRepliedByOperatorException extends DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('The contact has no successfully sent operator reply.', 0, $previous);
    }
}
