<?php

namespace Businesses\Wiki\Group\UseCase\Query\GetGroup;

use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Group\Domain\ValueObject\GroupIdentifier;

class GetGroupInput implements GetGroupInputPort
{
    public function __construct(
        private GroupIdentifier $groupIdentifier,
        private Translation $translation,
    ) {
    }

    public function groupIdentifier(): GroupIdentifier
    {
        return $this->groupIdentifier;
    }

    public function translation(): Translation
    {
        return $this->translation;
    }
}
