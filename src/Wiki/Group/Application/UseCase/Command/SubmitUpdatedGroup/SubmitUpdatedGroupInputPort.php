<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\SubmitUpdatedGroup;

use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;

interface SubmitUpdatedGroupInputPort
{
    public function groupIdentifier(): GroupIdentifier;
}
