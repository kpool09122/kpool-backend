<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\AddPrincipalToPrincipalGroup;

use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;

interface AddPrincipalToPrincipalGroupInputPort
{
    public function principalGroupIdentifier(): PrincipalGroupIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;
}
