<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Domain\Repository;

use Source\Analyze\Principal\Domain\Entity\Policy;
use Source\Analyze\Principal\Domain\ValueObject\PolicyIdentifier;

interface PolicyRepositoryInterface
{
    public function save(Policy $policy): void;

    /** @param PolicyIdentifier[] $identifiers
     * @return Policy[] */
    public function findByIds(array $identifiers): array;
}
