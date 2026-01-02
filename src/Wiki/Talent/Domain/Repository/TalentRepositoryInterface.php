<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Repository;

use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\Entity\Talent;

interface TalentRepositoryInterface
{
    public function findById(TalentIdentifier $identifier): ?Talent;

    public function save(Talent $talent): void;
}
