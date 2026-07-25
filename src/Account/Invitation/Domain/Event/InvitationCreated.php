<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Domain\Event;

use Source\Account\Invitation\Domain\ValueObject\InvitationIdentifier;
use Source\Shared\Domain\ValueObject\OneTimeToken;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class InvitationCreated
{
    public function __construct(
        public InvitationIdentifier $invitationIdentifier,
        public AccountIdentifier $accountIdentifier,
        public IdentityIdentifier $invitedByIdentityIdentifier,
        public Email $email,
        public OneTimeToken $token,
    ) {
    }
}
