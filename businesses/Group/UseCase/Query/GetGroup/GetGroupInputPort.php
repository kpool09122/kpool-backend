<?php

namespace Businesses\Group\UseCase\Query\GetGroup;

use Businesses\Group\Domain\ValueObject\GroupIdentifier;

interface GetGroupInputPort
{
    public function groupIdentifier(): GroupIdentifier;
}
