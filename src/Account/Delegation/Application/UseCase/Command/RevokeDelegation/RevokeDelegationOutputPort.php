<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Command\RevokeDelegation;

use Source\Account\Delegation\Domain\Entity\Delegation;

interface RevokeDelegationOutputPort
{
    public function setDelegation(Delegation $delegation): void;
}
