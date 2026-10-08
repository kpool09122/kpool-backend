<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class ProvisionPrincipalInput implements ProvisionPrincipalInputPort
{
    public function __construct(
        private IdentityIdentifier $identityIdentifier,
        private AccountIdentifier $accountIdentifier,
    ) {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }
}
