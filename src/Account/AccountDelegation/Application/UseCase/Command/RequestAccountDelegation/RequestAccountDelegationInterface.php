<?php

declare(strict_types=1);

namespace Source\Account\AccountDelegation\Application\UseCase\Command\RequestAccountDelegation;

interface RequestAccountDelegationInterface
{
    public function process(RequestAccountDelegationInputPort $input, RequestAccountDelegationOutputPort $output): void;
}
