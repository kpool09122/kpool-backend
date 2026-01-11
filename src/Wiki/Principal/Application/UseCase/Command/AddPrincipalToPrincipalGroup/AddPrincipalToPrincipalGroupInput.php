<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\AddPrincipalToPrincipalGroup;

use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class AddPrincipalToPrincipalGroupInput implements AddPrincipalToPrincipalGroupInputPort
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
