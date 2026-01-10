<?php

declare(strict_types=1);

namespace Source\Account\Domain\Factory;

use Source\Account\Domain\Entity\Account;
use Source\Account\Domain\ValueObject\AccountName;
use Source\Account\Domain\ValueObject\AccountType;
use Source\Account\Domain\ValueObject\ContractInfo;
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
