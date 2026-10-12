<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\WithdrawAccountCategoryChangeRequest;

use Source\Account\Account\Domain\Entity\AccountCategoryChangeRequest;

interface WithdrawAccountCategoryChangeRequestOutputPort
{
    public function setRequest(AccountCategoryChangeRequest $request): void;
}
