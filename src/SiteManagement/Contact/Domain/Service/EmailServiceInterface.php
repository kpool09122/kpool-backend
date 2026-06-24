<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Domain\Service;

use Source\Shared\Domain\ValueObject\Email;
use Source\SiteManagement\Contact\Domain\Entity\Contact;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;

interface EmailServiceInterface
{
    public function sendContactToUser(
        Contact $contact,
    ): void;

    public function sendContactToAdministrator(
        Contact $contact,
    ): void;

    public function sendReplyToUser(
        Email $toEmail,
        ReplyContent $content,
    ): void;
}
