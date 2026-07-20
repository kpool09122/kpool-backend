<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup;

use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class RemovePrincipalFromPrincipalGroupInput implements RemovePrincipalFromPrincipalGroupInputPort
{
    public function __construct(
        private PrincipalGroupIdentifier $principalGroupIdentifier,
        private IdentityIdentifier $principalIdentifier,
    ) {
    }

    public function principalGroupIdentifier(): PrincipalGroupIdentifier
    {
        return $this->principalGroupIdentifier;
    }

    public function principalIdentifier(): IdentityIdentifier
    {
        return $this->principalIdentifier;
    }
}
