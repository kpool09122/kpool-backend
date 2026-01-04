<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\ApproveDelegation;

use Source\Account\Domain\Entity\OperationDelegation;

interface ApproveDelegationInterface
{
    public function process(ApproveDelegationInputPort $input): OperationDelegation;
}
