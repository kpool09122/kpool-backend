<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Entity;

use Source\Identity\Domain\Exception\PasskeyUserAlreadyLinkedException;
use Source\Identity\Domain\ValueObject\PasskeyUserIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class PasskeyUser
{
    public function __construct(
        private readonly PasskeyUserIdentifier $identifier,
        private ?IdentityIdentifier $identityIdentifier,
    ) {
    }

    public function identifier(): PasskeyUserIdentifier
    {
        return $this->identifier;
    }

    public function identityIdentifier(): ?IdentityIdentifier
    {
        return $this->identityIdentifier;
    }

    public function linkToIdentity(IdentityIdentifier $identityIdentifier): void
    {
        if ($this->identityIdentifier !== null) {
            throw new PasskeyUserAlreadyLinkedException();
        }

        $this->identityIdentifier = $identityIdentifier;
    }
}
