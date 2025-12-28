<?php

declare(strict_types=1);

namespace Source\SiteManagement\User\Application\UseCase\Command\ProvisionUser;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class ProvisionUserInput implements ProvisionUserInputPort
{
    public function __construct(
        private IdentityIdentifier $identityIdentifier,
    ) {
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }
}
