<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\CreateAccount;

use Source\Account\Domain\Entity\AccountMembership;
use Source\Account\Domain\ValueObject\AccountName;
use Source\Account\Domain\ValueObject\AccountType;
use Source\Account\Domain\ValueObject\ContractInfo;
use Source\Shared\Domain\ValueObject\Email;

readonly class CreateAccountInput implements CreateAccountInputPort
{
    /**
     * @param Email $email
     * @param AccountType $accountType
     * @param AccountName $accountName
     * @param ContractInfo $contractInfo
     * @param AccountMembership[] $initialMembers
     */
    public function __construct(
        private Email $email,
        private AccountType $accountType,
        private AccountName $accountName,
        private ContractInfo $contractInfo,
        private array $initialMembers,
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

    /**
     * @return AccountMembership[]
     */
    public function initialMembers(): array
    {
        return $this->initialMembers;
    }
}
