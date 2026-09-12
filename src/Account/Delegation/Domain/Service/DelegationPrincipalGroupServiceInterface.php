<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Domain\Service;

use Source\Account\Delegation\Domain\Entity\AccountDelegation;

interface DelegationPrincipalGroupServiceInterface
{
    public function createFor(AccountDelegation $delegation): void;
}
