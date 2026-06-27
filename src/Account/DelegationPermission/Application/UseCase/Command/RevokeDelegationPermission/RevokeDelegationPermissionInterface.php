<?php

declare(strict_types=1);

namespace Source\Account\DelegationPermission\Application\UseCase\Command\RevokeDelegationPermission;

use Source\Account\DelegationPermission\Application\Exception\DelegationPermissionNotFoundException;

interface RevokeDelegationPermissionInterface
{
    /**
     * @throws DelegationPermissionNotFoundException
     */
    public function process(RevokeDelegationPermissionInputPort $input): void;
}
