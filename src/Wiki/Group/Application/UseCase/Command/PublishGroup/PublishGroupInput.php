<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\PublishGroup;

use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class PublishGroupInput implements PublishGroupInputPort
{
    public function __construct(
        private GroupIdentifier     $groupIdentifier,
        private ?GroupIdentifier    $publishedGroupIdentifier,
        private PrincipalIdentifier $principalIdentifier,
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

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }
}
