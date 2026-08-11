<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RejectAccountCategoryChangeRequest;

use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\RejectionReason;
use Source\Account\Principal\Domain\Entity\Principal;

interface RejectAccountCategoryChangeRequestInputPort
{
    public function requestIdentifier(): AccountCategoryChangeRequestIdentifier;

    public function principal(): Principal;

    public function rejectionReason(): RejectionReason;
}
