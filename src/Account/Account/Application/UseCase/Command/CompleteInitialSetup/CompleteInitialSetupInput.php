<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\CompleteInitialSetup;

use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class CompleteInitialSetupInput implements CompleteInitialSetupInputPort
{
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private AccountType $accountType,
    ) {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function accountType(): AccountType
    {
        return $this->accountType;
    }
}
