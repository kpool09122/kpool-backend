<?php

declare(strict_types=1);

namespace Source\Account\AccountDelegation\Application\UseCase\Command\RequestAccountDelegation;

use Source\Account\AccountDelegation\Domain\Entity\AccountDelegation;

interface RequestAccountDelegationOutputPort
{
    public function setDelegation(AccountDelegation $delegation): void;
}
