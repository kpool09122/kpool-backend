<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\UpdatePasskey;

use Source\Identity\Domain\ValueObject\PasskeyCredentialIdentifier;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface UpdatePasskeyInputPort
{
    public function identityIdentifier(): IdentityIdentifier;

    public function passkeyIdentifier(): PasskeyCredentialIdentifier;

    public function displayName(): PasskeyDisplayName;
}
