<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RequestAccountTypeChange;

use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface RequestAccountTypeChangeInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function principal(): Principal;

    public function requestedAccountType(): AccountType;
}
