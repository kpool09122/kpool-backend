<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Domain\Service;

use Source\SiteManagement\Contact\Domain\Entity\Contact;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;

interface ContactEmailServiceInterface
{
    public function sendContactToUser(
        Contact $contact,
    ): void;

    public function sendContactToAdministrator(
        Contact $contact,
    ): void;

    public function sendReplyToUser(
        Contact $contact,
        ReplyContent $content,
    ): void;
}
