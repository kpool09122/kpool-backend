<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\RevokeDelegationPermission;

interface RevokeDelegationPermissionInterface
{
    public function process(RevokeDelegationPermissionInputPort $input): void;
}
