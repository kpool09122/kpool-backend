<?php

declare(strict_types=1);

namespace Source\Account\IdentityGroup\Application\UseCase\Command\DeleteIdentityGroup;

use Source\Account\Shared\Domain\ValueObject\IdentityGroupIdentifier;

readonly class DeleteIdentityGroupInput implements DeleteIdentityGroupInputPort
{
    public function __construct(
        private IdentityGroupIdentifier $identityGroupIdentifier,
    ) {
    }

    public function identityGroupIdentifier(): IdentityGroupIdentifier
    {
        return $this->identityGroupIdentifier;
    }
}
