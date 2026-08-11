<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\CreateAccount;

use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\Phone;

readonly class CreateAccountInput implements CreateAccountInputPort
{
    public function __construct(
        private Email $email,
        private AccountType $accountType,
        private AccountName $accountName,
        private ?IdentityIdentifier $identityIdentifier = null,
        private Language $language = Language::ENGLISH,
        private ?Phone $phone = null,
        private ?string $addressCountryCode = null,
        private ?string $addressAdministrativeAreaCode = null,
        private ?string $addressPostalCode = null,
        private ?string $addressLocality = null,
        private ?string $addressLine1 = null,
        private ?string $addressLine2 = null,
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

    public function identityIdentifier(): ?IdentityIdentifier
    {
        return $this->identityIdentifier;
    }

    public function language(): Language
    {
        return $this->language;
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
