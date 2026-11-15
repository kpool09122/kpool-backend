<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use Source\Identity\Domain\ValueObject\AccountPrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AuthorizationAccount
{
    public function __construct(
        public IdentityIdentifier $identityIdentifier,
        public AccountIdentifier $accountIdentifier,
        public AccountPrincipalIdentifier $principalIdentifier,
        public string $name,
    ) {
    }
}
