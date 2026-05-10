<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Query\GetCurrentPrincipal;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class GetCurrentPrincipalInput implements GetCurrentPrincipalInputPort
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
