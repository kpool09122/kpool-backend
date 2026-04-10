<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Command\RevokeDelegation;

interface RevokeDelegationInterface
{
    public function process(RevokeDelegationInputPort $input, RevokeDelegationOutputPort $output): void;
}
