<?php

declare(strict_types=1);

namespace Source\Account\DelegationPermission\Application\UseCase\Command\RevokeDelegationPermission;

interface RevokeDelegationPermissionInterface
{
    public function process(RevokeDelegationPermissionInputPort $input): void;
}
