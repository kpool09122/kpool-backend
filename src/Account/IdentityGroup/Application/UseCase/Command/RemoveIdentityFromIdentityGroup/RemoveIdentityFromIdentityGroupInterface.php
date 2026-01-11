<?php

declare(strict_types=1);

namespace Source\Account\IdentityGroup\Application\UseCase\Command\RemoveIdentityFromIdentityGroup;

use Source\Account\IdentityGroup\Domain\Entity\IdentityGroup;

interface RemoveIdentityFromIdentityGroupInterface
{
    public function process(RemoveIdentityFromIdentityGroupInputPort $input): IdentityGroup;
}
