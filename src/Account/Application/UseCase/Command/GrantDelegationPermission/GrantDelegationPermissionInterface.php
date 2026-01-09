<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\GrantDelegationPermission;

use Source\Account\Domain\Entity\DelegationPermission;

interface GrantDelegationPermissionInterface
{
    public function process(GrantDelegationPermissionInputPort $input): DelegationPermission;
}
