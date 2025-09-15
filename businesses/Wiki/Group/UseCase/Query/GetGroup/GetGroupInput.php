<?php

namespace Businesses\Wiki\Group\UseCase\Query\GetGroup;

use Businesses\Wiki\Group\Domain\ValueObject\GroupIdentifier;

class GetGroupInput implements GetGroupInputPort
{
    public function __construct(
        private GroupIdentifier $groupIdentifier
    ) {
    }

    public function groupIdentifier(): GroupIdentifier
    {
        return $this->groupIdentifier;
    }
}
