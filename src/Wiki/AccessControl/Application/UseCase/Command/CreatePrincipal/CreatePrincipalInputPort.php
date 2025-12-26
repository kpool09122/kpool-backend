<?php

declare(strict_types=1);

namespace Source\Wiki\AccessControl\Application\UseCase\Command\CreatePrincipal;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;

interface CreatePrincipalInputPort
{
    public function identityIdentifier(): IdentityIdentifier;
}
