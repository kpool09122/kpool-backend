<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Repository;

use Source\Account\Account\Domain\Entity\Account;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;

interface AccountRepositoryInterface
{
    public function save(Account $account): void;

    public function findById(AccountIdentifier $identifier): ?Account;

    /**
     * @param AccountIdentifier[] $identifiers
     * @return Account[]
     */
    public function findByIds(array $identifiers): array;

    public function findByEmail(Email $email): ?Account;

    public function delete(Account $account): void;
}
