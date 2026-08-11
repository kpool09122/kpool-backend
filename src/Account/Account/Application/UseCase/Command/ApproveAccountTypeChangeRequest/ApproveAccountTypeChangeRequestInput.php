<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\ApproveAccountTypeChangeRequest;

use Source\Account\Account\Domain\ValueObject\AccountTypeChangeRequestIdentifier;
use Source\Account\Principal\Domain\Entity\Principal;

readonly class ApproveAccountTypeChangeRequestInput implements ApproveAccountTypeChangeRequestInputPort
{
    public function __construct(private AccountTypeChangeRequestIdentifier $requestIdentifier, private Principal $principal)
    {
    }

    public function requestIdentifier(): AccountTypeChangeRequestIdentifier
    {
        return $this->requestIdentifier;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }
}
