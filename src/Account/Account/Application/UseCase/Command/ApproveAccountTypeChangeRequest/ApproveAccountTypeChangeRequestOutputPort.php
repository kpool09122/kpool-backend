<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\ApproveAccountTypeChangeRequest;

use Source\Account\Account\Domain\Entity\AccountTypeChangeRequest;

interface ApproveAccountTypeChangeRequestOutputPort
{
    public function setRequest(AccountTypeChangeRequest $request): void;
}
