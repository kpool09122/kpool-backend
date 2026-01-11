<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Command\ApproveDelegation;

use Source\Account\Delegation\Domain\Entity\Delegation;

interface ApproveDelegationInterface
{
    public function process(ApproveDelegationInputPort $input): Delegation;
}
