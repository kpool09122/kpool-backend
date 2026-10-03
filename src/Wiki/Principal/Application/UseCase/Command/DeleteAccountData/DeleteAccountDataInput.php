<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\DeleteAccountData;

use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class DeleteAccountDataInput implements DeleteAccountDataInputPort
{
    public function __construct(private AccountIdentifier $accountIdentifier)
    {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }
}
