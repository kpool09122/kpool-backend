<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\Event;

use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class PrincipalCreated
{
    public function __construct(
        public PrincipalIdentifier $principalIdentifier,
        public IdentityIdentifier $identityIdentifier,
        public AccountIdentifier $accountIdentifier,
    ) {
    }
}
