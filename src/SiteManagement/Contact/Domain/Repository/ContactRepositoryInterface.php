<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Domain\Repository;

use Source\SiteManagement\Contact\Domain\Entity\Contact;

interface ContactRepositoryInterface
{
    public function save(Contact $contact): void;
}
