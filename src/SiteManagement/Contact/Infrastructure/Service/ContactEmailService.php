<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Infrastructure\Service;

use Application\Mail\ContactAcceptedMail;
use Application\Mail\ContactReceivedMail;
use Application\Mail\ContactReplyMail;
use Illuminate\Support\Facades\Mail;
use Source\Shared\Domain\ValueObject\Email;
use Source\SiteManagement\Contact\Domain\Entity\Contact;
use Source\SiteManagement\Contact\Domain\Service\ContactEmailServiceInterface;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;

readonly class ContactEmailService implements ContactEmailServiceInterface
{
    public function __construct(
        private Email $administratorEmail,
    ) {
    }

    public function sendContactToUser(Contact $contact): void
    {
        Mail::to((string) $contact->email())->send(
            new ContactAcceptedMail($contact)
        );
    }

    public function sendContactToAdministrator(Contact $contact): void
    {
        Mail::to((string) $this->administratorEmail)->send(
            new ContactReceivedMail($contact)
        );
    }

    public function sendReplyToUser(Contact $contact, ReplyContent $content): void
    {
        Mail::to((string) $contact->email())->send(
            new ContactReplyMail($content, $contact->language())
        );
    }
}
