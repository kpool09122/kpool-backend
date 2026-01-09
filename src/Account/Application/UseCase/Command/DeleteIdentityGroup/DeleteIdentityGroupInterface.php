<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\DeleteIdentityGroup;

interface DeleteIdentityGroupInterface
{
    public function process(DeleteIdentityGroupInputPort $input): void;
}
