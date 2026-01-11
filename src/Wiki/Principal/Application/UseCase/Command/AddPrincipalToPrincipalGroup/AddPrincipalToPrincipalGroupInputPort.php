<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\AddPrincipalToPrincipalGroup;

use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface AddPrincipalToPrincipalGroupInputPort
{
    public function principalGroupIdentifier(): PrincipalGroupIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;
}
