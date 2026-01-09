<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\RevokeDelegationPermission;

use Source\Account\Domain\ValueObject\DelegationPermissionIdentifier;

readonly class RevokeDelegationPermissionInput implements RevokeDelegationPermissionInputPort
{
    public function __construct(
        private DelegationPermissionIdentifier $delegationPermissionIdentifier,
    ) {
    }

    public function delegationPermissionIdentifier(): DelegationPermissionIdentifier
    {
        return $this->delegationPermissionIdentifier;
    }
}
