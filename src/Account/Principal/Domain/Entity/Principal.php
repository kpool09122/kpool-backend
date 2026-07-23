<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\Entity;

use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class Principal
{
    public function __construct(
        private PrincipalIdentifier $principalIdentifier,
        private IdentityIdentifier $identityIdentifier,
        private AccountIdentifier $accountIdentifier,
    ) {
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }
}
