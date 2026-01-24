<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Domain\Repository;

use Source\SiteManagement\Contact\Domain\Entity\Contact;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;

interface ContactRepositoryInterface
{
    public function save(Contact $contact): void;

    public function findById(ContactIdentifier $contactIdentifier): ?Contact;
}
