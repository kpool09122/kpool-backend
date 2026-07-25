<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Event;

use Source\Shared\Domain\ValueObject\OneTimeToken;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class IdentityCreatedViaInvitation
{
    public function __construct(
        public IdentityIdentifier $identityIdentifier,
        public OneTimeToken $oneTimeToken,
    ) {
    }
}
