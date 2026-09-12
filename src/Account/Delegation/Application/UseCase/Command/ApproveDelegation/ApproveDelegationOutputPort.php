<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Command\ApproveDelegation;

use Source\Account\Delegation\Domain\Entity\AccountDelegation;

interface ApproveDelegationOutputPort
{
    public function setDelegation(AccountDelegation $delegation): void;
}
