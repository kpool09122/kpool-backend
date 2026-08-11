<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\ApproveAccountCategoryChangeRequest;

use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestIdentifier;
use Source\Account\Principal\Domain\Entity\Principal;

readonly class ApproveAccountCategoryChangeRequestInput implements ApproveAccountCategoryChangeRequestInputPort
{
    public function __construct(private AccountCategoryChangeRequestIdentifier $requestIdentifier, private Principal $principal)
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
}
