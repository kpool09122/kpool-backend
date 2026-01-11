<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Factory;

use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\ContractInfo;
use Source\Shared\Domain\ValueObject\Email;

interface AccountFactoryInterface
{
    public function create(
        Email $email,
        AccountType $type,
        AccountName $name,
        ContractInfo $contractInfo,
    ): Account;
}
