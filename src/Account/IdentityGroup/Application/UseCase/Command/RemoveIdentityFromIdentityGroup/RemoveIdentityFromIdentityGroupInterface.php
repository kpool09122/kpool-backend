<?php

declare(strict_types=1);

namespace Source\Account\IdentityGroup\Application\UseCase\Command\RemoveIdentityFromIdentityGroup;

interface RemoveIdentityFromIdentityGroupInterface
{
    public function process(RemoveIdentityFromIdentityGroupInputPort $input, RemoveIdentityFromIdentityGroupOutputPort $output): void;
}
