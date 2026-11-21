<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Domain\Repository;

use Source\Monetization\Principal\Domain\Entity\Policy;
use Source\Monetization\Principal\Domain\ValueObject\PolicyIdentifier;

interface PolicyRepositoryInterface
{
    public function save(Policy $policy): void;

    /** @param PolicyIdentifier[] $identifiers
     * @return array<string, Policy> */
    public function findByIds(array $identifiers): array;
}
