<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\CreateAccount;

use Source\Account\Domain\ValueObject\AccountName;
use Source\Account\Domain\ValueObject\AccountType;
use Source\Account\Domain\ValueObject\ContractInfo;
use Source\Shared\Domain\ValueObject\Email;

interface CreateAccountInputPort
{
    public function email(): Email;

    public function accountType(): AccountType;

    public function accountName(): AccountName;

    public function contractInfo(): ContractInfo;
}
