<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\RollbackGroup;

use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;

readonly class RollbackGroupInput implements RollbackGroupInputPort
{
    public function __construct(
        private PrincipalIdentifier $principalIdentifier,
        private GroupIdentifier $groupIdentifier,
        private Version $targetVersion,
    ) {
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function groupIdentifier(): GroupIdentifier
    {
        return $this->groupIdentifier;
    }

    public function targetVersion(): Version
    {
        return $this->targetVersion;
    }
}
