<?php

declare(strict_types=1);

namespace Source\Account\AccountDelegation\Application\UseCase\Command\RequestAccountDelegation;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface RequestAccountDelegationInputPort
{
    public function principal(): Principal;

    public function targetAccountIdentifier(): AccountIdentifier;
}
