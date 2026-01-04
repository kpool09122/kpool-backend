<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SwitchIdentity;

use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface SwitchIdentityInputPort
{
    public function currentIdentityIdentifier(): IdentityIdentifier;

    public function targetDelegationIdentifier(): ?DelegationIdentifier;
}
