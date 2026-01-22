<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Factory;

use Source\Wiki\Principal\Domain\Entity\PromotionHistory;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface PromotionHistoryFactoryInterface
{
    public function create(
        PrincipalIdentifier $principalIdentifier,
        string $fromRole,
        string $toRole,
        ?string $reason,
    ): PromotionHistory;
}
