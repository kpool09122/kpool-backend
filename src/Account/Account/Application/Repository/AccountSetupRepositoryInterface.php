<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Repository;

use Source\Account\Account\Domain\Entity\Account;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface AccountSetupRepositoryInterface
{
    public function findByIdForUpdate(AccountIdentifier $identifier): ?Account;

    public function save(Account $account): void;
}
