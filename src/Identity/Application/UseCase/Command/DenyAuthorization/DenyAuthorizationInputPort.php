<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\DenyAuthorization;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface DenyAuthorizationInputPort
{
    public function requestIdentifier(): string;

    public function identityIdentifier(): IdentityIdentifier;
}
