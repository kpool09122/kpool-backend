<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\CreateAccount;

use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;

readonly class CreateAccountInput implements CreateAccountInputPort
{
    public function __construct(
        private Email $email,
        private AccountType $accountType,
        private AccountName $accountName,
        private ?IdentityIdentifier $identityIdentifier = null,
        private Language $language = Language::ENGLISH,
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
}
