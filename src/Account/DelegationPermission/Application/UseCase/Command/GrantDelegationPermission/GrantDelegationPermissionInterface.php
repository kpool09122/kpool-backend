<?php

declare(strict_types=1);

namespace Source\Account\DelegationPermission\Application\UseCase\Command\GrantDelegationPermission;

interface GrantDelegationPermissionInterface
{
    public function process(GrantDelegationPermissionInputPort $input, GrantDelegationPermissionOutputPort $output): void;
}
