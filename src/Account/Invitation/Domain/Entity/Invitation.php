<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Domain\Entity;

use DateTimeImmutable;
use Source\Account\Invitation\Domain\Exception\InvitationAlreadyUsedOrRevokedException;
use Source\Account\Invitation\Domain\Exception\InvitationExpiredException;
use Source\Account\Invitation\Domain\Exception\InvitationNotPendingException;
use Source\Account\Invitation\Domain\ValueObject\InvitationIdentifier;
use Source\Account\Invitation\Domain\ValueObject\InvitationStatus;
use Source\Account\Invitation\Domain\ValueObject\InvitationToken;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class Invitation
{
    public function __construct(
        private readonly InvitationIdentifier $invitationIdentifier,
        private readonly AccountIdentifier $accountIdentifier,
        private readonly IdentityIdentifier $invitedByIdentityIdentifier,
        private readonly Email $email,
        private readonly InvitationToken $token,
        private InvitationStatus $status,
        private readonly DateTimeImmutable $expiresAt,
        private ?IdentityIdentifier $acceptedByIdentityIdentifier,
        private ?DateTimeImmutable $acceptedAt,
        private readonly DateTimeImmutable $createdAt,
    ) {
    }

    public function invitationIdentifier(): InvitationIdentifier
    {
        return $this->invitationIdentifier;
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function invitedByIdentityIdentifier(): IdentityIdentifier
    {
        return $this->invitedByIdentityIdentifier;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function token(): InvitationToken
    {
        return $this->token;
    }

    public function status(): InvitationStatus
    {
        return $this->status;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function acceptedByIdentityIdentifier(): ?IdentityIdentifier
    {
        return $this->acceptedByIdentityIdentifier;
    }

    public function acceptedAt(): ?DateTimeImmutable
    {
        return $this->acceptedAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isExpired(): bool
    {
        return new DateTimeImmutable() > $this->expiresAt;
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function assertAcceptable(): void
    {
        if (! $this->status->isPending()) {
            throw new InvitationAlreadyUsedOrRevokedException();
        }

        if ($this->isExpired()) {
            throw new InvitationExpiredException();
        }
    }

    public function accept(IdentityIdentifier $identityIdentifier): void
    {
        $this->assertAcceptable();

        $this->status = InvitationStatus::ACCEPTED;
        $this->acceptedByIdentityIdentifier = $identityIdentifier;
        $this->acceptedAt = new DateTimeImmutable();
    }

    public function revoke(): void
    {
        if (! $this->status->isPending()) {
            throw new InvitationNotPendingException();
        }

        $this->status = InvitationStatus::REVOKED;
    }
}
