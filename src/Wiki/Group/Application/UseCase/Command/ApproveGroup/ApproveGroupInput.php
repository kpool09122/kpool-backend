<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\ApproveGroup;

use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;

readonly class ApproveGroupInput implements ApproveGroupInputPort
{
    public function __construct(
        private GroupIdentifier  $groupIdentifier,
        private ?GroupIdentifier $publishedGroupIdentifier,
        private Principal        $principal,
    ) {
    }

    public function groupIdentifier(): GroupIdentifier
    {
        return $this->groupIdentifier;
    }

    public function publishedGroupIdentifier(): ?GroupIdentifier
    {
        return $this->publishedGroupIdentifier;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }
}
