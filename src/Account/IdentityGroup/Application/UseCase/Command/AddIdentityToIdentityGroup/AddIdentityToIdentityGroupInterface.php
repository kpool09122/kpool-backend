<?php

declare(strict_types=1);

namespace Source\Account\IdentityGroup\Application\UseCase\Command\AddIdentityToIdentityGroup;

interface AddIdentityToIdentityGroupInterface
{
    public function process(AddIdentityToIdentityGroupInputPort $input, AddIdentityToIdentityGroupOutputPort $output): void;
}
