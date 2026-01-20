<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Event;

use Source\Account\Invitation\Domain\ValueObject\InvitationToken;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class IdentityCreatedViaInvitation
{
    public function __construct(
        public IdentityIdentifier $identityIdentifier,
        public InvitationToken $invitationToken,
    ) {
    }
}
