<?php

declare(strict_types=1);

namespace Source\Account\IdentityGroup\Application\UseCase\Command\RemoveIdentityFromIdentityGroup;

use Source\Account\Shared\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class RemoveIdentityFromIdentityGroupInput implements RemoveIdentityFromIdentityGroupInputPort
{
    public function __construct(
        private IdentityGroupIdentifier $identityGroupIdentifier,
        private IdentityIdentifier $identityIdentifier,
    ) {
    }

    public function identityGroupIdentifier(): IdentityGroupIdentifier
    {
        return $this->identityGroupIdentifier;
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }
}
