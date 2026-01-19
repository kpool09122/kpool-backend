<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Domain\Event;

use DateTimeImmutable;
use Source\Account\Invitation\Domain\ValueObject\InvitationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class InvitationAccepted
{
    public function __construct(
        public InvitationIdentifier $invitationIdentifier,
        public AccountIdentifier $accountIdentifier,
        public IdentityIdentifier $acceptedByIdentityIdentifier,
        public DateTimeImmutable $acceptedAt,
    ) {
    }
}
