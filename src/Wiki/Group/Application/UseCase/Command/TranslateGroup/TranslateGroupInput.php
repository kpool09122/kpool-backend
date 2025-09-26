<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\TranslateGroup;

use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;

readonly class TranslateGroupInput implements TranslateGroupInputPort
{
    public function __construct(
        private GroupIdentifier  $groupIdentifier,
    ) {
    }

    public function groupIdentifier(): GroupIdentifier
    {
        return $this->groupIdentifier;
    }
}
