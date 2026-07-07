<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query\GetIdentityProfile;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class GetIdentityProfileInput implements GetIdentityProfileInputPort
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
