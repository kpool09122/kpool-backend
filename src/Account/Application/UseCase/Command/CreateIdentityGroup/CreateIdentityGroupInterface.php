<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\CreateIdentityGroup;

use Source\Account\Domain\Entity\IdentityGroup;

interface CreateIdentityGroupInterface
{
    public function process(CreateIdentityGroupInputPort $input): IdentityGroup;
}
