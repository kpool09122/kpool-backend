<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup;

use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class RemovePrincipalFromPrincipalGroupInput implements RemovePrincipalFromPrincipalGroupInputPort
{
    public function __construct(
        private PrincipalGroupIdentifier $principalGroupIdentifier,
        private PrincipalIdentifier $principalIdentifier,
    ) {
    }

    public function principalGroupIdentifier(): PrincipalGroupIdentifier
    {
        return $this->principalGroupIdentifier;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }
}
