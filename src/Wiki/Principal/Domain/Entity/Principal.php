<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Entity;

use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Exception\CannotChangeNonDelegatedPrincipalException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class Principal
{
    /**
     * @param PrincipalIdentifier $principalIdentifier
     * @param IdentityIdentifier $identityIdentifier
     * @param DelegationIdentifier|null $delegationIdentifier
     * @param bool $enabled
     */
    public function __construct(
        private readonly PrincipalIdentifier $principalIdentifier,
        private readonly IdentityIdentifier  $identityIdentifier,
        private readonly ?DelegationIdentifier $delegationIdentifier = null,
        private bool                         $enabled = true,
    ) {
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }

    public function delegationIdentifier(): ?DelegationIdentifier
    {
        return $this->delegationIdentifier;
    }

    public function isDelegatedPrincipal(): bool
    {
        return $this->delegationIdentifier !== null;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        if (! $this->isDelegatedPrincipal()) {
            throw new CannotChangeNonDelegatedPrincipalException();
        }

        $this->enabled = $enabled;
    }
}
