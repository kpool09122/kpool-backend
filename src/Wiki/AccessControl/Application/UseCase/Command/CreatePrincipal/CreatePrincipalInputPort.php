<?php

declare(strict_types=1);

namespace Source\Wiki\AccessControl\Application\UseCase\Command\CreatePrincipal;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface CreatePrincipalInputPort
{
    public function identityIdentifier(): IdentityIdentifier;
}
