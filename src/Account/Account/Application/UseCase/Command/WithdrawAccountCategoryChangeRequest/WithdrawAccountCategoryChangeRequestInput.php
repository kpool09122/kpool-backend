<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\WithdrawAccountCategoryChangeRequest;

use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestIdentifier;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class WithdrawAccountCategoryChangeRequestInput implements WithdrawAccountCategoryChangeRequestInputPort
{
    public function __construct(private AccountIdentifier $accountIdentifier, private AccountCategoryChangeRequestIdentifier $requestIdentifier, private Principal $principal)
    {
    }

    public function requestIdentifier(): AccountCategoryChangeRequestIdentifier
    {
        return $this->requestIdentifier;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }
}
