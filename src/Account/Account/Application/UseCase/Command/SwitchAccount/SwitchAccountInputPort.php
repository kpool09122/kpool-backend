<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\SwitchAccount;

use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface SwitchAccountInputPort
{
    public function identityIdentifier(): IdentityIdentifier;

    public function originalPrincipalIdentifier(): PrincipalIdentifier;

    public function targetDelegationIdentifier(): ?DelegationIdentifier;
}
