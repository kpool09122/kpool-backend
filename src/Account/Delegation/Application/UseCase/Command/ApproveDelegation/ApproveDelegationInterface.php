<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Command\ApproveDelegation;

interface ApproveDelegationInterface
{
    public function process(ApproveDelegationInputPort $input, ApproveDelegationOutputPort $output): void;
}
