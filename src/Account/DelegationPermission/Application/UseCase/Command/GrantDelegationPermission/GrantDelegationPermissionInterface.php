<?php

declare(strict_types=1);

namespace Source\Account\DelegationPermission\Application\UseCase\Command\GrantDelegationPermission;

use Source\Account\Principal\Application\Exception\PrincipalGroupNotFoundException;

interface GrantDelegationPermissionInterface
{
    /**
     * @throws PrincipalGroupNotFoundException
     */
    public function process(GrantDelegationPermissionInputPort $input, GrantDelegationPermissionOutputPort $output): void;
}
