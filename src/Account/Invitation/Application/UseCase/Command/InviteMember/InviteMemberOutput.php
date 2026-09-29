<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Application\UseCase\Command\InviteMember;

use Source\Account\Invitation\Domain\Entity\Invitation;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Application\Exception\OutputNotInitializedException;

class InviteMemberOutput implements InviteMemberOutputPort
{
    /** @var array<Invitation> */
    private array $invitations = [];
    private ?PrincipalIdentifier $invitedByPrincipalIdentifier = null;

    /**
     * @param array<Invitation> $invitations
     */
    public function setInvitations(array $invitations, PrincipalIdentifier $invitedByPrincipalIdentifier): void
    {
        $this->invitations = $invitations;
        $this->invitedByPrincipalIdentifier = $invitedByPrincipalIdentifier;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function toArray(): array
    {
        if ($this->invitations === []) {
            return [];
        }

        if ($this->invitedByPrincipalIdentifier === null) {
            throw new OutputNotInitializedException('Invited by principal identifier is not set.');
        }

        return array_map(fn (Invitation $invitation) => [
            'invitationIdentifier' => (string) $invitation->invitationIdentifier(),
            'accountIdentifier' => (string) $invitation->accountIdentifier(),
            'invitedByPrincipalIdentifier' => (string) $this->invitedByPrincipalIdentifier,
            'email' => (string) $invitation->email(),
            'token' => (string) $invitation->token(),
            'status' => $invitation->status()->value,
            'expiresAt' => $invitation->expiresAt()->format('Y-m-d\TH:i:sP'),
            'createdAt' => $invitation->createdAt()->format('Y-m-d\TH:i:sP'),
        ], $this->invitations);
    }
}
