<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\ApproveUpdatedGroup;

use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;

interface ApproveUpdatedGroupInputPort
{
    public function groupIdentifier(): GroupIdentifier;

    public function publishedGroupIdentifier(): ?GroupIdentifier;
}
