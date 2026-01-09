<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\DeleteIdentityGroup;

use Source\Account\Domain\ValueObject\IdentityGroupIdentifier;

interface DeleteIdentityGroupInputPort
{
    public function identityGroupIdentifier(): IdentityGroupIdentifier;
}
