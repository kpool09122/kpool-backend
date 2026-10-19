<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Entity;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Exception\PrincipalStatusChangeForbiddenException;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class Principal
{
    /**
     * @param PrincipalIdentifier $principalIdentifier
     * @param IdentityIdentifier $identityIdentifier
     * @param AccountIdentifier $accountIdentifier
     * @param DelegationIdentifier|null $delegationIdentifier
     * @param PrincipalStatus $status
     */
    public function __construct(
        private readonly PrincipalIdentifier $principalIdentifier,
        private readonly IdentityIdentifier  $identityIdentifier,
        private readonly AccountIdentifier $accountIdentifier,
        private readonly ?DelegationIdentifier $delegationIdentifier = null,
        private PrincipalStatus $status = PrincipalStatus::ACTIVE,
    ) {
    }

    public function status(): PrincipalStatus
    {
        return $this->status;
    }

    public function changeStatus(PrincipalStatus $status): void
    {
        if ($this->status === $status) {
            return;
        }
        if ($this->status === PrincipalStatus::FORCED_STOP || $status === PrincipalStatus::FORCED_STOP) {
            throw new PrincipalStatusChangeForbiddenException();
        }
        $this->status = $status;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function delegationIdentifier(): ?DelegationIdentifier
    {
        return $this->delegationIdentifier;
    }

    public function isDelegatedPrincipal(): bool
    {
        return $this->delegationIdentifier !== null;
    }
}
