<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\TranslateGroup;

use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;

interface TranslateGroupInputPort
{
    public function groupIdentifier(): GroupIdentifier;

    public function principal(): Principal;
}
