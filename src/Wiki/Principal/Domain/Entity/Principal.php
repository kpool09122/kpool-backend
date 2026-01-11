<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Entity;

use DomainException;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class Principal
{
    /**
     * @param PrincipalIdentifier $principalIdentifier
     * @param IdentityIdentifier $identityIdentifier
     * @param string|null $agencyId
     * @param string[] $groupIds
     * @param string[] $talentIds
     * @param DelegationIdentifier|null $delegationIdentifier
     * @param bool $enabled
     */
    public function __construct(
        private readonly PrincipalIdentifier $principalIdentifier,
        private readonly IdentityIdentifier  $identityIdentifier,
        private readonly ?string             $agencyId,
        private readonly array               $groupIds,
        private readonly array               $talentIds,
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

    public function agencyId(): ?string
    {
        return $this->agencyId;
    }

    /**
     * @return string[]
     */
    public function groupIds(): array
    {
        return $this->groupIds;
    }

    /**
     * @return string[]
     */
    public function talentIds(): array
    {
        return $this->talentIds;
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
            throw new DomainException('Cannot change enabled status of non-delegated principal.');
        }

        $this->enabled = $enabled;
    }
}
