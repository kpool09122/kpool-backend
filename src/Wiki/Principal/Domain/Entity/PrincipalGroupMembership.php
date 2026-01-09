<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Entity;

use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class PrincipalGroupMembership
{
    public function __construct(
        private readonly PrincipalIdentifier $principalIdentifier,
        private readonly PrincipalGroupIdentifier $principalGroupIdentifier,
    ) {
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function principalGroupIdentifier(): PrincipalGroupIdentifier
    {
        return $this->principalGroupIdentifier;
    }
}
