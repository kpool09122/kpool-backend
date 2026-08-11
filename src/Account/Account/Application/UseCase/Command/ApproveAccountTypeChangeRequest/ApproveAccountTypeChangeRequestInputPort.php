<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\ApproveAccountTypeChangeRequest;

use Source\Account\Account\Domain\ValueObject\AccountTypeChangeRequestIdentifier;
use Source\Account\Principal\Domain\Entity\Principal;

interface ApproveAccountTypeChangeRequestInputPort
{
    public function requestIdentifier(): AccountTypeChangeRequestIdentifier;

    public function principal(): Principal;
}
