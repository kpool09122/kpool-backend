<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\SubmitUpdatedGroup;

use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;

readonly class SubmitUpdatedGroupInput implements SubmitUpdatedGroupInputPort
{
    public function __construct(
        private GroupIdentifier $groupIdentifier,
    ) {
    }

    public function groupIdentifier(): GroupIdentifier
    {
        return $this->groupIdentifier;
    }
}
