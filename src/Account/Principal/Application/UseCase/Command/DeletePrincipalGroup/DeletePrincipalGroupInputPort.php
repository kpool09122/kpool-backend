<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\DeletePrincipalGroup;

use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;

interface DeletePrincipalGroupInputPort
{
    public function principalGroupIdentifier(): PrincipalGroupIdentifier;
}
