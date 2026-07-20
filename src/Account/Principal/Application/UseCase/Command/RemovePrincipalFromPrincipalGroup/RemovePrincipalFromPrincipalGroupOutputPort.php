<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup;

use Source\Account\Principal\Domain\Entity\PrincipalGroup;

interface RemovePrincipalFromPrincipalGroupOutputPort
{
    public function setPrincipalGroup(PrincipalGroup $principalGroup): void;
}
