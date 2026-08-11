<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Factory;

use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Factory\AccountFactoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountDocuments;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\ContactAddress;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Phone;

readonly class AccountFactory implements AccountFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $generator,
    ) {
    }

    public function create(
        Email $email,
        AccountType $type,
        AccountName $name,
        ?Phone $phone = null,
        ?ContactAddress $address = null,
    ): Account {
        return new Account(
            new AccountIdentifier($this->generator->generate()),
            $email,
            $type,
            $name,
            AccountStatus::ACTIVE,
            AccountCategory::GENERAL,
            DeletionReadinessChecklist::ready(),
            new AccountDocuments(),
            $phone,
            $address,
        );
    }
}
