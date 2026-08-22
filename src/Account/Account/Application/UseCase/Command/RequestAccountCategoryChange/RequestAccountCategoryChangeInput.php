<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class RequestAccountCategoryChangeInput implements RequestAccountCategoryChangeInputPort
{
    public function __construct(private AccountIdentifier $accountIdentifier, private Principal $principal, private AccountCategory $requestedAccountCategory)
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

    public function requestedAccountCategory(): AccountCategory
    {
        return $this->requestedAccountCategory;
    }
}
