<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\UpdatePasskey;

use Source\Identity\Domain\ValueObject\PasskeyCredentialIdentifier;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class UpdatePasskeyInput implements UpdatePasskeyInputPort
{
    public function __construct(
        private IdentityIdentifier $identityIdentifier,
        private PasskeyCredentialIdentifier $passkeyIdentifier,
        private PasskeyDisplayName $displayName,
    ) {
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }

    public function passkeyIdentifier(): PasskeyCredentialIdentifier
    {
        return $this->passkeyIdentifier;
    }

    public function displayName(): PasskeyDisplayName
    {
        return $this->displayName;
    }
}
