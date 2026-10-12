<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\WithdrawAccountCategoryChangeRequest;

use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestIdentifier;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface WithdrawAccountCategoryChangeRequestInputPort
{
    public function requestIdentifier(): AccountCategoryChangeRequestIdentifier;

    public function principal(): Principal;

    public function accountIdentifier(): AccountIdentifier;
}
