<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Service;

use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class CurrentAccount
{
    public function __construct(
        public IdentityIdentifier $originalIdentityIdentifier,
        public AccountIdentifier $originalAccountIdentifier,
        public PrincipalIdentifier $originalPrincipalIdentifier,
        public AccountIdentifier $effectiveAccountIdentifier,
        public PrincipalIdentifier $effectivePrincipalIdentifier,
        public ?DelegationIdentifier $delegationIdentifier,
    ) {
    }
}
