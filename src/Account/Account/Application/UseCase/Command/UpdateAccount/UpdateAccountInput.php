<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\UpdateAccount;

use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class UpdateAccountInput implements UpdateAccountInputPort
{
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private Principal $principal,
        private AccountName $accountName,
    ) {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }

    public function accountName(): AccountName
    {
        return $this->accountName;
    }
}
