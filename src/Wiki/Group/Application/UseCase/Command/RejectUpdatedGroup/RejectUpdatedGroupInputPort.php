<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\RejectUpdatedGroup;

use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;

interface RejectUpdatedGroupInputPort
{
    public function groupIdentifier(): GroupIdentifier;
}
