<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\CreateAccount;

use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\ContractInfo;
use Source\Shared\Domain\ValueObject\Email;

readonly class CreateAccountInput implements CreateAccountInputPort
{
    public function __construct(
        private Email $email,
        private AccountType $accountType,
        private AccountName $accountName,
        private ContractInfo $contractInfo,
    ) {
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function accountType(): AccountType
    {
        return $this->accountType;
    }

    public function accountName(): AccountName
    {
        return $this->accountName;
    }

    public function contractInfo(): ContractInfo
    {
        return $this->contractInfo;
    }
}
