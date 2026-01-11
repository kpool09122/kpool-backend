<?php

declare(strict_types=1);

namespace Source\Account\IdentityGroup\Application\UseCase\Command\DeleteIdentityGroup;

use Source\Account\Shared\Domain\ValueObject\IdentityGroupIdentifier;

interface DeleteIdentityGroupInputPort
{
    public function identityGroupIdentifier(): IdentityGroupIdentifier;
}
