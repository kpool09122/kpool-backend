<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\AutoCreateGroup;

use Source\Wiki\Group\Domain\ValueObject\AutoGroupCreationPayload;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface AutoCreateGroupInputPort
{
    public function payload(): AutoGroupCreationPayload;

    public function principalIdentifier(): PrincipalIdentifier;
}
