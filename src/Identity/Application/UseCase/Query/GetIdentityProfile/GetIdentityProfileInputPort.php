<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query\GetIdentityProfile;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface GetIdentityProfileInputPort
{
    public function identityIdentifier(): IdentityIdentifier;
}
