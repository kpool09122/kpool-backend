<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\SwitchAccount;

use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class SwitchAccountInput implements SwitchAccountInputPort
{
    public function __construct(
        private IdentityIdentifier $identityIdentifier,
        private PrincipalIdentifier $originalPrincipalIdentifier,
        private ?DelegationIdentifier $targetDelegationIdentifier,
    ) {
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }

    public function originalPrincipalIdentifier(): PrincipalIdentifier
    {
        return $this->originalPrincipalIdentifier;
    }

    public function targetDelegationIdentifier(): ?DelegationIdentifier
    {
        return $this->targetDelegationIdentifier;
    }
}
