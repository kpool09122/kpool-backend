<?php

declare(strict_types=1);

namespace Source\Account\DelegationPermission\Application\UseCase\Command\RevokeDelegationPermission;

use Source\Account\DelegationPermission\Domain\ValueObject\DelegationPermissionIdentifier;

interface RevokeDelegationPermissionInputPort
{
    public function delegationPermissionIdentifier(): DelegationPermissionIdentifier;
}
