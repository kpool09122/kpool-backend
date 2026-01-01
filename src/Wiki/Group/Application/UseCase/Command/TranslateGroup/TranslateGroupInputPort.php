<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\TranslateGroup;

use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface TranslateGroupInputPort
{
    public function groupIdentifier(): GroupIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;
}
