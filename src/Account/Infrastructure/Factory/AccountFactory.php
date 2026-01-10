<?php

declare(strict_types=1);

namespace Source\Account\Infrastructure\Factory;

use Source\Account\Domain\Entity\Account;
use Source\Account\Domain\Factory\AccountFactoryInterface;
use Source\Account\Domain\ValueObject\AccountCategory;
use Source\Account\Domain\ValueObject\AccountName;
use Source\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Domain\ValueObject\AccountType;
use Source\Account\Domain\ValueObject\ContractInfo;
use Source\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;

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
        ContractInfo $contractInfo,
    ): Account {
        return new Account(
            new AccountIdentifier($this->generator->generate()),
            $email,
            $type,
            $name,
            $contractInfo,
            AccountStatus::ACTIVE,
            AccountCategory::GENERAL,
            DeletionReadinessChecklist::ready(),
        );
    }
}
