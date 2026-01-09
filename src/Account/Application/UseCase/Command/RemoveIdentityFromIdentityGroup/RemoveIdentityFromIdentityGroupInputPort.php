<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\RemoveIdentityFromIdentityGroup;

use Source\Account\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface RemoveIdentityFromIdentityGroupInputPort
{
    public function identityGroupIdentifier(): IdentityGroupIdentifier;

    public function identityIdentifier(): IdentityIdentifier;
}
