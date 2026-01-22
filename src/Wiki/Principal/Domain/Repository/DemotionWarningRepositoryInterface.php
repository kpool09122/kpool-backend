<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Repository;

use Source\Wiki\Principal\Domain\Entity\DemotionWarning;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface DemotionWarningRepositoryInterface
{
    public function save(DemotionWarning $warning): void;

    public function findByPrincipal(PrincipalIdentifier $principalIdentifier): ?DemotionWarning;

    public function delete(DemotionWarning $warning): void;

    /**
     * @return DemotionWarning[]
     */
    public function findAll(): array;
}
