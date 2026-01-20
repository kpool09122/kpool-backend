<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Domain\Service;

use Source\Account\Invitation\Domain\Entity\Invitation;

interface InvitationMailServiceInterface
{
    public function sendInvitationEmail(Invitation $invitation): void;
}
