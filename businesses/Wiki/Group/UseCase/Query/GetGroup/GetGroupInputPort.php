<?php

namespace Businesses\Wiki\Group\UseCase\Query\GetGroup;

use Businesses\Wiki\Group\Domain\ValueObject\GroupIdentifier;

interface GetGroupInputPort
{
    public function groupIdentifier(): GroupIdentifier;
}
