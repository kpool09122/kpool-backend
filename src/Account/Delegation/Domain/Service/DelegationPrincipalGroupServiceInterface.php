<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Domain\Service;

use Source\Account\Delegation\Domain\Entity\Delegation;

interface DelegationPrincipalGroupServiceInterface
{
    public function createFor(Delegation $delegation): void;
}
