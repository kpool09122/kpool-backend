<?php

declare(strict_types=1);

namespace Source\Account\IdentityGroup\Application\UseCase\Command\AddIdentityToIdentityGroup;

use Source\Account\IdentityGroup\Application\Exception\IdentityGroupNotFoundException;

interface AddIdentityToIdentityGroupInterface
{
    /**
     * @throws IdentityGroupNotFoundException
     */
    public function process(AddIdentityToIdentityGroupInputPort $input, AddIdentityToIdentityGroupOutputPort $output): void;
}
