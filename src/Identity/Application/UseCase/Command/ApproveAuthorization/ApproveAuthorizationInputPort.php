<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\ApproveAuthorization;

use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface ApproveAuthorizationInputPort
{
    public function requestIdentifier(): string;

    public function identityIdentifier(): IdentityIdentifier;

    /** @return list<AuthorizationScope> */
    public function scopes(): array;
}
