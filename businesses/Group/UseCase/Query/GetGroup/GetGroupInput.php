<?php

namespace Businesses\Group\UseCase\Query\GetGroup;

use Businesses\Group\Domain\ValueObject\GroupIdentifier;

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
