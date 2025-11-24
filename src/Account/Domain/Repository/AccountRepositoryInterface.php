<?php

declare(strict_types=1);

namespace Source\Account\Domain\Repository;

use Source\Account\Domain\Entity\Account;
use Source\Account\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;

interface AccountRepositoryInterface
{
    public function save(Account $account): void;

    public function findById(AccountIdentifier $identifier): ?Account;

    public function findByEmail(Email $email): ?Account;
}
