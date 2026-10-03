<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface IdentitySessionServiceInterface
{
    public function terminate(IdentityIdentifier $identityIdentifier): void;
}
