<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query\GetAuthenticatedIdentity;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface GetAuthenticatedIdentityInputPort
{
    public function identityIdentifier(): IdentityIdentifier;
}
