<?php

declare(strict_types=1);

namespace Source\Account\Domain\Factory;

use Source\Account\Domain\Entity\Account;
use Source\Account\Domain\Entity\AccountMembership;
use Source\Account\Domain\ValueObject\AccountIdentifier;
use Source\Account\Domain\ValueObject\AccountName;
use Source\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Domain\ValueObject\AccountType;
use Source\Account\Domain\ValueObject\ContractInfo;
use Source\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Email;

readonly class AccountFactory implements AccountFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $generator,
    ) {
    }

    /**
     * @param Email $email
     * @param AccountType $type
     * @param AccountName $name
     * @param ContractInfo $contractInfo
     * @param AccountMembership[] $memberships
     * @return Account
     */
    public function create(
        Email $email,
        AccountType $type,
        AccountName $name,
        ContractInfo $contractInfo,
        array $memberships
    ): Account {
        return new Account(
            new AccountIdentifier($this->generator->generate()),
            $email,
            $type,
            $name,
            $contractInfo,
            AccountStatus::ACTIVE,
            $memberships,
            DeletionReadinessChecklist::ready(),
        );
    }
}
