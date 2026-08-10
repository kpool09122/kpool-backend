<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RequestAccountTypeChange;

use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class RequestAccountTypeChangeInput implements RequestAccountTypeChangeInputPort
{
    public function __construct(private AccountIdentifier $accountIdentifier, private Principal $principal, private AccountType $requestedAccountType)
    {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }

    public function requestedAccountType(): AccountType
    {
        return $this->requestedAccountType;
    }
}
