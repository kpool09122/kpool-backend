<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface ActorContextServiceInterface
{
    public function forget(IdentityIdentifier $identityIdentifier): void;
}
