<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\RevokeAuthorization;

use Source\Identity\Domain\ValueObject\AuthorizationConnectionIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface RevokeAuthorizationInputPort
{
    public function identityIdentifier(): IdentityIdentifier;

    public function connectionIdentifier(): AuthorizationConnectionIdentifier;
}
