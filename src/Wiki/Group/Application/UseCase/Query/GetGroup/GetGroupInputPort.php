<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Query\GetGroup;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;

interface GetGroupInputPort
{
    public function groupIdentifier(): GroupIdentifier;

    public function language(): Language;
}
