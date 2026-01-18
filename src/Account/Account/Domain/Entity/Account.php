<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Entity;

use Source\Account\Account\Domain\Exception\AccountDeletionBlockedException;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\ContractInfo;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;

class Account
{
    public function __construct(
        private readonly AccountIdentifier $accountIdentifier,
        private readonly Email $email,
        private AccountType $type,
        private AccountName $name,
        private ?ContractInfo $contractInfo,
        private AccountStatus $status,
        private AccountCategory $accountCategory,
        private DeletionReadinessChecklist $deletionReadiness,
    ) {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function type(): AccountType
    {
        return $this->type;
    }

    public function name(): AccountName
    {
        return $this->name;
    }

    public function contractInfo(): ?ContractInfo
    {
        return $this->contractInfo;
    }

    public function setContractInfo(ContractInfo $contractInfo): void
    {
        $this->contractInfo = $contractInfo;
    }

    public function status(): AccountStatus
    {
        return $this->status;
    }

    public function accountCategory(): AccountCategory
    {
        return $this->accountCategory;
    }

    public function setAccountCategory(AccountCategory $category): void
    {
        $this->accountCategory = $category;
    }

    public function deletionReadiness(): DeletionReadinessChecklist
    {
        return $this->deletionReadiness;
    }

    /**
     * @throws AccountDeletionBlockedException
     */
    public function assertDeletable(): void
    {
        $this->deletionReadiness->assertReady();
    }
}
