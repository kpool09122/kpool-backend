<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\Repository;

use Source\Account\Principal\Domain\Entity\Policy;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;

interface PolicyRepositoryInterface
{
    public function save(Policy $policy): void;

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
