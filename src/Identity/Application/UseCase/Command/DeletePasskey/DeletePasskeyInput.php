<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\DeletePasskey;

use Source\Identity\Domain\ValueObject\PasskeyCredentialIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class DeletePasskeyInput implements DeletePasskeyInputPort
{
    public function __construct(
        private IdentityIdentifier $identityIdentifier,
        private PasskeyCredentialIdentifier $passkeyIdentifier,
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
}
