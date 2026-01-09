<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\AddIdentityToIdentityGroup;

use Source\Account\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface AddIdentityToIdentityGroupInputPort
{
    public function identityGroupIdentifier(): IdentityGroupIdentifier;

    public function identityIdentifier(): IdentityIdentifier;
}
