<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\Entity;

use Source\Account\Principal\Domain\Exception\PrincipalStatusChangeForbiddenException;
use Source\Account\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class Principal
{
    public function __construct(
        private readonly PrincipalIdentifier $principalIdentifier,
        private readonly IdentityIdentifier $identityIdentifier,
        private readonly AccountIdentifier $accountIdentifier,
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
}
