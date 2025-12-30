<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\RejectGroup;

use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class RejectGroupInput implements RejectGroupInputPort
{
    public function __construct(
        private GroupIdentifier     $groupIdentifier,
        private PrincipalIdentifier $principalIdentifier,
    ) {
    }

    public function groupIdentifier(): GroupIdentifier
    {
        return $this->groupIdentifier;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }
}
