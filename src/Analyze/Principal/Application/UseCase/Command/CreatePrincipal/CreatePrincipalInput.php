<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Application\UseCase\Command\CreatePrincipal;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class CreatePrincipalInput implements CreatePrincipalInputPort
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
