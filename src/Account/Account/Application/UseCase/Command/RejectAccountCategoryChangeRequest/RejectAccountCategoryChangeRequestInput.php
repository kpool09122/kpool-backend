<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RejectAccountCategoryChangeRequest;

use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\RejectionReason;
use Source\Account\Principal\Domain\Entity\Principal;

readonly class RejectAccountCategoryChangeRequestInput implements RejectAccountCategoryChangeRequestInputPort
{
    public function __construct(private AccountCategoryChangeRequestIdentifier $requestIdentifier, private Principal $principal, private RejectionReason $rejectionReason)
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

    public function rejectionReason(): RejectionReason
    {
        return $this->rejectionReason;
    }
}
