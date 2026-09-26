<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Command\RejectDelegation;

interface RejectDelegationInterface
{
    public function process(RejectDelegationInputPort $input): void;
}
