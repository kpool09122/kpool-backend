<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Domain\Service;

use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;

interface ReplyNumberServiceInterface
{
    public function next(ContactIdentifier $contactIdentifier): int;
}
