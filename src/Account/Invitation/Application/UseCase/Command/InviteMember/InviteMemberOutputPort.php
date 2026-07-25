<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Application\UseCase\Command\InviteMember;

use Source\Account\Invitation\Domain\Entity\Invitation;

interface InviteMemberOutputPort
{
    /**
     * @param array<Invitation> $invitations
     */
    public function setInvitations(array $invitations): void;
}
