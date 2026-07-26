<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Domain\Service;

use Source\Account\Invitation\Domain\Entity\Invitation;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;

interface InvitationMailServiceInterface
{
    public function sendInvitationEmail(Invitation $invitation): void;

    public function sendExistingEmailNotification(Email $email, Language $language): void;
}
