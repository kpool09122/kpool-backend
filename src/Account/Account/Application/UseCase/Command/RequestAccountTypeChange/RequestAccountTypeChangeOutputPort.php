<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RequestAccountTypeChange;

use Source\Account\Account\Domain\Entity\AccountTypeChangeRequest;

interface RequestAccountTypeChangeOutputPort
{
    public function setRequest(AccountTypeChangeRequest $request): void;
}
