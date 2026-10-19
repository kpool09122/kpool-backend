<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Domain\Entity;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Domain\Exception\PrincipalStatusChangeForbiddenException;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalStatus;

final class Principal
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
