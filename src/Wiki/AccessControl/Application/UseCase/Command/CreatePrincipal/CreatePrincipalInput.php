<?php

declare(strict_types=1);

namespace Source\Wiki\AccessControl\Application\UseCase\Command\CreatePrincipal;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;

readonly class CreatePrincipalInput implements CreatePrincipalInputPort
{
    public function __construct(
        private IdentityIdentifier $identityIdentifier,
    ) {
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }
}
