<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Repository;

use Source\Wiki\Principal\Domain\Entity\Policy;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;

interface PolicyRepositoryInterface
{
    public function save(Policy $policy): void;

    public function findById(PolicyIdentifier $policyIdentifier): ?Policy;

    /**
     * @param PolicyIdentifier[] $policyIdentifiers
     * @return array<string, Policy> policyIdentifier をキーとした連想配列
     */
    public function findByIds(array $policyIdentifiers): array;

    /**
     * @return array<Policy>
     */
    public function findAll(): array;

    public function delete(Policy $policy): void;
}
