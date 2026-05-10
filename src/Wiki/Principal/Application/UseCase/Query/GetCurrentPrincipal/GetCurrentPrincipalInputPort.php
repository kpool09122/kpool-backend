<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Query\GetCurrentPrincipal;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface GetCurrentPrincipalInputPort
{
    public function identityIdentifier(): IdentityIdentifier;
}
