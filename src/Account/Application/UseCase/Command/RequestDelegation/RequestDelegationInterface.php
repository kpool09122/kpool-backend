<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\RequestDelegation;

use Source\Account\Domain\Entity\OperationDelegation;

interface RequestDelegationInterface
{
    public function process(RequestDelegationInputPort $input): OperationDelegation;
}
