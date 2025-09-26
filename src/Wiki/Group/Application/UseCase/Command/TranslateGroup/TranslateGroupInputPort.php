<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\TranslateGroup;

use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;

interface TranslateGroupInputPort
{
    public function groupIdentifier(): GroupIdentifier;
}
