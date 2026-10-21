<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Service;

use Source\Account\Account\Application\Exception\DocumentStorageFailedException;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Entity\AccountCategoryChangeRequest;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface AccountCategoryChangeRequestSnapshotServiceInterface
{
    /** @throws DocumentStorageFailedException */
    public function capture(AccountCategoryChangeRequest $request, Account $account): void;

    public function deleteForAccount(AccountIdentifier $accountIdentifier): void;
}
