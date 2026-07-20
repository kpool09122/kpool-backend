<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\CreatePrincipalGroup;

use Source\Account\Principal\Domain\Entity\PrincipalGroup;

interface CreatePrincipalGroupOutputPort
{
    public function setPrincipalGroup(PrincipalGroup $principalGroup): void;
}
