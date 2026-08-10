<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange;

use Source\Account\Account\Domain\Entity\AccountCategoryChangeRequest;

interface RequestAccountCategoryChangeOutputPort
{
    public function setRequest(AccountCategoryChangeRequest $request): void;
}
