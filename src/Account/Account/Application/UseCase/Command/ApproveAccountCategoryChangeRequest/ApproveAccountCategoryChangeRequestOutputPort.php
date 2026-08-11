<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\ApproveAccountCategoryChangeRequest;

use Source\Account\Account\Domain\Entity\AccountCategoryChangeRequest;

interface ApproveAccountCategoryChangeRequestOutputPort
{
    public function setRequest(AccountCategoryChangeRequest $request): void;
}
