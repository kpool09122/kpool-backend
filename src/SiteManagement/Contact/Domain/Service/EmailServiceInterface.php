<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Domain\Service;

use Source\SiteManagement\Contact\Domain\Entity\Contact;

interface EmailServiceInterface
{
    public function sendContactToUser(
        Contact $contact,
    ): void;

    public function sendContactToAdministrator(
        Contact $contact,
    ): void;
}
