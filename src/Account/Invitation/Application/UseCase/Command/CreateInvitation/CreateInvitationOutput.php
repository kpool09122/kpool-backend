<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Application\UseCase\Command\CreateInvitation;

use Source\Account\Invitation\Domain\Entity\Invitation;

class CreateInvitationOutput implements CreateInvitationOutputPort
{
    /** @var array<Invitation> */
    private array $invitations = [];

    /**
     * @param array<Invitation> $invitations
     */
    public function setInvitations(array $invitations): void
    {
        $this->invitations = $invitations;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(fn (Invitation $invitation) => [
            'invitationIdentifier' => (string) $invitation->invitationIdentifier(),
            'accountIdentifier' => (string) $invitation->accountIdentifier(),
            'invitedByIdentityIdentifier' => (string) $invitation->invitedByIdentityIdentifier(),
            'email' => (string) $invitation->email(),
            'token' => (string) $invitation->token(),
            'status' => $invitation->status()->value,
            'expiresAt' => $invitation->expiresAt()->format('Y-m-d\TH:i:sP'),
            'createdAt' => $invitation->createdAt()->format('Y-m-d\TH:i:sP'),
        ], $this->invitations);
    }
}
