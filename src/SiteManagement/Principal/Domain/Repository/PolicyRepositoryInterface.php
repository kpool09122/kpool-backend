<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Domain\Repository;

use Source\SiteManagement\Principal\Domain\Entity\Policy;
use Source\SiteManagement\Principal\Domain\ValueObject\PolicyIdentifier;

interface PolicyRepositoryInterface
{
    public function save(Policy $policy): void;

    /** @param PolicyIdentifier[] $identifiers
     * @return Policy[] */
    public function findByIds(array $identifiers): array;
}
