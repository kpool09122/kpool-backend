<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\ApproveGroup;

use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface ApproveGroupInputPort
{
    public function groupIdentifier(): GroupIdentifier;

    public function publishedGroupIdentifier(): ?GroupIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;
}
