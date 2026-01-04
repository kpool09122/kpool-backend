<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\RevokeDelegation;

use Source\Account\Domain\Entity\OperationDelegation;

interface RevokeDelegationInterface
{
    public function process(RevokeDelegationInputPort $input): OperationDelegation;
}
