<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Query\GetGroup;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;

readonly class GetGroupInput implements GetGroupInputPort
{
    public function __construct(
        private GroupIdentifier $groupIdentifier,
        private Language        $langauge,
    ) {
    }

    public function groupIdentifier(): GroupIdentifier
    {
        return $this->groupIdentifier;
    }

    public function language(): Language
    {
        return $this->langauge;
    }
}
