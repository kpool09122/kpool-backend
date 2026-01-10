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
     * @return array<Policy>
     */
    public function findAll(): array;

    public function delete(Policy $policy): void;
}
