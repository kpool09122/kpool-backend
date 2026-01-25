<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Domain\Repository;

use Source\Wiki\Grading\Domain\Entity\PromotionHistory;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface PromotionHistoryRepositoryInterface
{
    public function save(PromotionHistory $history): void;

    /**
     * @return PromotionHistory[]
     */
    public function findByPrincipal(PrincipalIdentifier $principalIdentifier): array;
}
