<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\UpdateAccount;

use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\ContactAddress;
use Source\Shared\Domain\ValueObject\Phone;

readonly class UpdateAccountInput implements UpdateAccountInputPort
{
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private Principal $principal,
        private AccountName $accountName,
        private ?Phone $phone = null,
        private ?ContactAddress $address = null,
    ) {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }

    public function accountName(): AccountName
    {
        return $this->accountName;
    }

    public function phone(): ?Phone
    {
        return $this->phone;
    }

    public function address(): ?ContactAddress
    {
        return $this->address;
    }
}
