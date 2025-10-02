<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\RejectGroup;

use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;

interface RejectGroupInputPort
{
    public function groupIdentifier(): GroupIdentifier;
}
