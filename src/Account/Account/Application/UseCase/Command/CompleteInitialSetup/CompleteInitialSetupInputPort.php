<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\CompleteInitialSetup;

use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface CompleteInitialSetupInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function accountType(): AccountType;
}
