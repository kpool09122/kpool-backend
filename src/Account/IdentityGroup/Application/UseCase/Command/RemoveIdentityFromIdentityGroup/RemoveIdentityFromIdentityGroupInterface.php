<?php

declare(strict_types=1);

namespace Source\Account\IdentityGroup\Application\UseCase\Command\RemoveIdentityFromIdentityGroup;

use Source\Account\IdentityGroup\Application\Exception\CannotRemoveLastOwnerException;
use Source\Account\IdentityGroup\Application\Exception\IdentityGroupNotFoundException;

interface RemoveIdentityFromIdentityGroupInterface
{
    /**
     * @throws CannotRemoveLastOwnerException
     * @throws IdentityGroupNotFoundException
     */
    public function process(RemoveIdentityFromIdentityGroupInputPort $input, RemoveIdentityFromIdentityGroupOutputPort $output): void;
}
