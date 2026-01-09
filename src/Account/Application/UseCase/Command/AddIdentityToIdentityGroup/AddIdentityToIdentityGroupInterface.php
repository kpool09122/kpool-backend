<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\AddIdentityToIdentityGroup;

use Source\Account\Domain\Entity\IdentityGroup;

interface AddIdentityToIdentityGroupInterface
{
    public function process(AddIdentityToIdentityGroupInputPort $input): IdentityGroup;
}
