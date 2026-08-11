<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\CreateAccount;

use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\Phone;

interface CreateAccountInputPort
{
    public function email(): Email;

    public function accountType(): AccountType;

    public function accountName(): AccountName;

    public function identityIdentifier(): ?IdentityIdentifier;

    public function phone(): ?Phone;

    public function addressCountryCode(): ?string;

    public function addressAdministrativeAreaCode(): ?string;

    public function addressPostalCode(): ?string;

    public function addressLocality(): ?string;

    public function addressLine1(): ?string;

    public function addressLine2(): ?string;

    public function language(): Language;
}
