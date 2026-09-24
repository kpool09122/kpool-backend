<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\AddPasskeyOptions;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface AddPasskeyOptionsInputPort
{
    public function identityIdentifier(): IdentityIdentifier;
}
