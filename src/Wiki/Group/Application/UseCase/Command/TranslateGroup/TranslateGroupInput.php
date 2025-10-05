<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\TranslateGroup;

use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;

readonly class TranslateGroupInput implements TranslateGroupInputPort
{
    public function __construct(
        private GroupIdentifier  $groupIdentifier,
        private Principal $principal,
    ) {
    }

    public function groupIdentifier(): GroupIdentifier
    {
        return $this->groupIdentifier;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }
}
