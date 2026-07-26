<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Application\UseCase\Command\InviteMember;

use Source\Account\Invitation\Domain\Entity\Invitation;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;

interface InviteMemberOutputPort
{
    /**
     * @param array<Invitation> $invitations
     */
    public function setInvitations(array $invitations, PrincipalIdentifier $invitedByPrincipalIdentifier): void;
}
