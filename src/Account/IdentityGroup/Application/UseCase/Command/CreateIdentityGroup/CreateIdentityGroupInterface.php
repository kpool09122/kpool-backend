<?php

declare(strict_types=1);

namespace Source\Account\IdentityGroup\Application\UseCase\Command\CreateIdentityGroup;

interface CreateIdentityGroupInterface
{
    public function process(CreateIdentityGroupInputPort $input, CreateIdentityGroupOutputPort $output): void;
}
