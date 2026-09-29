<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreatePasskeyOptions;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface CreatePasskeyOptionsInputPort
{
    public function identityIdentifier(): IdentityIdentifier;
}
