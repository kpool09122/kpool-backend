<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\UpdateAccount;

use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Phone;

readonly class UpdateAccountInput implements UpdateAccountInputPort
{
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private Principal $principal,
        private AccountName $accountName,
        private ?Phone $phone = null,
        private ?string $addressCountryCode = null,
        private ?string $addressAdministrativeAreaCode = null,
        private ?string $addressPostalCode = null,
        private ?string $addressLocality = null,
        private ?string $addressLine1 = null,
        private ?string $addressLine2 = null,
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

    public function addressCountryCode(): ?string
    {
        return $this->addressCountryCode;
    }

    public function addressAdministrativeAreaCode(): ?string
    {
        return $this->addressAdministrativeAreaCode;
    }

    public function addressPostalCode(): ?string
    {
        return $this->addressPostalCode;
    }

    public function addressLocality(): ?string
    {
        return $this->addressLocality;
    }

    public function addressLine1(): ?string
    {
        return $this->addressLine1;
    }

    public function addressLine2(): ?string
    {
        return $this->addressLine2;
    }
}
