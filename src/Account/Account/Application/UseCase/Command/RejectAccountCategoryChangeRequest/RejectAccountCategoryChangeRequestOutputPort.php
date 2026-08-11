<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RejectAccountCategoryChangeRequest;

use Source\Account\Account\Domain\Entity\AccountCategoryChangeRequest;

interface RejectAccountCategoryChangeRequestOutputPort
{
    public function setRequest(AccountCategoryChangeRequest $request): void;
}
