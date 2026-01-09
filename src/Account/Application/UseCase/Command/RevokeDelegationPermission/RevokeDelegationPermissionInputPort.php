<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\RevokeDelegationPermission;

use Source\Account\Domain\ValueObject\DelegationPermissionIdentifier;

interface RevokeDelegationPermissionInputPort
{
    public function delegationPermissionIdentifier(): DelegationPermissionIdentifier;
}
