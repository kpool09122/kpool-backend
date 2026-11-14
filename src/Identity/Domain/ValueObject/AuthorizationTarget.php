<?php

declare(strict_types=1);

namespace Source\Identity\Domain\ValueObject;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;

readonly class AuthorizationTarget
{
    public function __construct(
        public AccountIdentifier $accountIdentifier,
        public ?DelegationIdentifier $delegationIdentifier = null,
    ) {
    }
}
