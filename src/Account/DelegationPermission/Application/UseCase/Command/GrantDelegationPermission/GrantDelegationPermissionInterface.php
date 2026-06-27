<?php

declare(strict_types=1);

namespace Source\Account\DelegationPermission\Application\UseCase\Command\GrantDelegationPermission;

use Source\Account\IdentityGroup\Application\Exception\IdentityGroupNotFoundException;

interface GrantDelegationPermissionInterface
{
    /**
     * @throws IdentityGroupNotFoundException
     */
    public function process(GrantDelegationPermissionInputPort $input, GrantDelegationPermissionOutputPort $output): void;
}
