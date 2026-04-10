<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Application\UseCase\Command\CreateInvitation;

use Source\Account\Invitation\Domain\Entity\Invitation;

interface CreateInvitationOutputPort
{
    /**
     * @param array<Invitation> $invitations
     */
    public function setInvitations(array $invitations): void;
}
