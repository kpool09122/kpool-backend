<?php

declare(strict_types=1);

namespace Source\Account\DelegationPermission\Application\UseCase\Command\GrantDelegationPermission;

use Source\Account\DelegationPermission\Domain\Entity\DelegationPermission;

interface GrantDelegationPermissionInterface
{
    public function process(GrantDelegationPermissionInputPort $input): DelegationPermission;
}
