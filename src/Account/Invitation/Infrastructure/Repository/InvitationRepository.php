<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Infrastructure\Repository;

use Application\Models\Account\Invitation as InvitationEloquent;
use DateTimeImmutable;
use Source\Account\Invitation\Domain\Entity\Invitation;
use Source\Account\Invitation\Domain\Repository\InvitationRepositoryInterface;
use Source\Account\Invitation\Domain\ValueObject\InvitationIdentifier;
use Source\Account\Invitation\Domain\ValueObject\InvitationStatus;
use Source\Account\Invitation\Domain\ValueObject\InvitationToken;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class InvitationRepository implements InvitationRepositoryInterface
{
    public function save(Invitation $invitation): void
    {
        InvitationEloquent::query()->updateOrCreate(
            ['id' => (string) $invitation->invitationIdentifier()],
            [
                'account_id' => (string) $invitation->accountIdentifier(),
                'invited_by_identity_id' => (string) $invitation->invitedByIdentityIdentifier(),
                'email' => (string) $invitation->email(),
                'token' => (string) $invitation->token(),
                'status' => $invitation->status()->value,
                'expires_at' => $invitation->expiresAt(),
                'accepted_by_identity_id' => $invitation->acceptedByIdentityIdentifier() !== null
                    ? (string) $invitation->acceptedByIdentityIdentifier()
                    : null,
                'accepted_at' => $invitation->acceptedAt(),
                'created_at' => $invitation->createdAt(),
            ]
        );
    }

    public function findByToken(InvitationToken $token): ?Invitation
    {
        $eloquent = InvitationEloquent::query()
            ->where('token', (string) $token)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function findPendingByAccountAndEmail(
        AccountIdentifier $accountIdentifier,
        Email $email
    ): ?Invitation {
        $eloquent = InvitationEloquent::query()
            ->where('account_id', (string) $accountIdentifier)
            ->where('email', (string) $email)
            ->where('status', InvitationStatus::PENDING->value)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    private function toDomainEntity(InvitationEloquent $eloquent): Invitation
    {
        return new Invitation(
            new InvitationIdentifier($eloquent->id),
            new AccountIdentifier($eloquent->account_id),
            new IdentityIdentifier($eloquent->invited_by_identity_id),
            new Email($eloquent->email),
            new InvitationToken($eloquent->token),
            InvitationStatus::from($eloquent->status),
            new DateTimeImmutable($eloquent->expires_at->toDateTimeString()),
            $eloquent->accepted_by_identity_id !== null
                ? new IdentityIdentifier($eloquent->accepted_by_identity_id)
                : null,
            $eloquent->accepted_at !== null
                ? new DateTimeImmutable($eloquent->accepted_at->toDateTimeString())
                : null,
            new DateTimeImmutable($eloquent->created_at->toDateTimeString()),
        );
    }
}
