<?php

namespace Businesses\Wiki\Group\UseCase\Query\GetGroup;

use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Group\Domain\ValueObject\GroupIdentifier;

interface GetGroupInputPort
{
    public function groupIdentifier(): GroupIdentifier;

    public function translation(): Translation;
}
