<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\RemoveIdentityFromIdentityGroup;

use Source\Account\Domain\Entity\IdentityGroup;

interface RemoveIdentityFromIdentityGroupInterface
{
    public function process(RemoveIdentityFromIdentityGroupInputPort $input): IdentityGroup;
}
