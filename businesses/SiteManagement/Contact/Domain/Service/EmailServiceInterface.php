<?php

declare(strict_types=1);

namespace Businesses\SiteManagement\Contact\Domain\Service;

use Businesses\SiteManagement\Contact\Domain\Entity\Contact;

interface EmailServiceInterface
{
    public function sendContactToUser(
        Contact $contact,
    ): void;

    public function sendContactToAdministrator(
        Contact $contact,
    ): void;
}
