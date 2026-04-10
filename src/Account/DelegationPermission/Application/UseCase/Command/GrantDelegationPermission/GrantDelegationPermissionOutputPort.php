<?php

declare(strict_types=1);

namespace Source\Account\DelegationPermission\Application\UseCase\Command\GrantDelegationPermission;

use Source\Account\DelegationPermission\Domain\Entity\DelegationPermission;

interface GrantDelegationPermissionOutputPort
{
    public function setDelegationPermission(DelegationPermission $delegationPermission): void;
}
