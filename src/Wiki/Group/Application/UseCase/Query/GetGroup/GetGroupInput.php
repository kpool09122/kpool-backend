<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Query\GetGroup;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;

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
