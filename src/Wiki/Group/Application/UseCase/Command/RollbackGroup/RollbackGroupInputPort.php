<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\RollbackGroup;

use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;

interface RollbackGroupInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    public function groupIdentifier(): GroupIdentifier;

    public function targetVersion(): Version;
}
