<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\DeleteAccount;

use Source\Account\Domain\ValueObject\AccountIdentifier;

readonly class DeleteAccountInput implements DeleteAccountInputPort
{
    public function __construct(
        private AccountIdentifier $accountIdentifier,
    ) {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }
}
