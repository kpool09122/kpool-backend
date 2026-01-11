<?php

declare(strict_types=1);

namespace Source\Account\IdentityGroup\Application\UseCase\Command\AddIdentityToIdentityGroup;

use Source\Account\IdentityGroup\Domain\Entity\IdentityGroup;

interface AddIdentityToIdentityGroupInterface
{
    public function process(AddIdentityToIdentityGroupInputPort $input): IdentityGroup;
}
