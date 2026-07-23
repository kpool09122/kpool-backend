<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Repository;

use Source\Account\Account\Domain\Entity\AccountVerification;
use Source\Account\Account\Domain\ValueObject\VerificationIdentifier;
use Source\Account\Account\Domain\ValueObject\VerificationStatus;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface AccountVerificationRepositoryInterface
{
    public function save(AccountVerification $entity): void;

    public function findById(VerificationIdentifier $id): ?AccountVerification;

    public function findByAccountId(AccountIdentifier $accountId): ?AccountVerification;

    public function findPendingByAccountId(AccountIdentifier $accountId): ?AccountVerification;

    public function existsPending(AccountIdentifier $accountId): bool;

    /**
     * @return AccountVerification[]
     */
    public function findByStatus(VerificationStatus $status, int $limit = 50, int $offset = 0): array;

    /**
     * @return AccountVerification[]
     */
    public function findAll(int $limit = 50, int $offset = 0): array;
}
