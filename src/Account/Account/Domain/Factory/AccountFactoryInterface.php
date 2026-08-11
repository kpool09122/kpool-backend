<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Factory;

use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\ContactAddress;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Phone;

interface AccountFactoryInterface
{
    public function create(
        Email $email,
        AccountType $type,
        AccountName $name,
        ?Phone $phone = null,
        ?ContactAddress $address = null,
    ): Account;
}
