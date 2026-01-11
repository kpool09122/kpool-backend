<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Command\RequestDelegation;

use Source\Account\Delegation\Domain\Entity\Delegation;

interface RequestDelegationInterface
{
    public function process(RequestDelegationInputPort $input): Delegation;
}
