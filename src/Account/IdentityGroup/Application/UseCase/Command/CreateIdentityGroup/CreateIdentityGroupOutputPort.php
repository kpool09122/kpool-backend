<?php

declare(strict_types=1);

namespace Source\Account\IdentityGroup\Application\UseCase\Command\CreateIdentityGroup;

use Source\Account\IdentityGroup\Domain\Entity\IdentityGroup;

interface CreateIdentityGroupOutputPort
{
    public function setIdentityGroup(IdentityGroup $identityGroup): void;
}
