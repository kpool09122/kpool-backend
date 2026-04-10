<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Command\RequestDelegation;

interface RequestDelegationInterface
{
    public function process(RequestDelegationInputPort $input, RequestDelegationOutputPort $output): void;
}
