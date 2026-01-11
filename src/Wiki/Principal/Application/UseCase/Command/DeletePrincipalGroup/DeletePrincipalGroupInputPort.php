<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\DeletePrincipalGroup;

use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;

interface DeletePrincipalGroupInputPort
{
    public function principalGroupIdentifier(): PrincipalGroupIdentifier;
}
