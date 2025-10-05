<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\SubmitGroup;

use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;

interface SubmitGroupInputPort
{
    public function groupIdentifier(): GroupIdentifier;

    public function principal(): Principal;
}
