<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\Repository;

use Source\Account\Principal\Domain\Entity\Policy;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface PolicyRepositoryInterface
{
    public function save(Policy $policy): void;

    public function findSystemByName(string $name): ?Policy;

    public function findByAccountIdAndName(AccountIdentifier $accountIdentifier, string $name): ?Policy;

    /**
     * @param PolicyIdentifier[] $policyIdentifiers
     * @return array<string, Policy> policyIdentifier をキーとした連想配列
     */
    public function findByIds(array $policyIdentifiers): array;

    /**
     * @return Policy[]
     */
    public function findAll(): array;
}
