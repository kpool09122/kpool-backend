<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\PublishGroup;

use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;

interface PublishGroupInputPort
{
    public function groupIdentifier(): GroupIdentifier;

    public function publishedGroupIdentifier(): ?GroupIdentifier;
}
